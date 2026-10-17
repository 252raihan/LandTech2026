<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\PartyIdentity;
use App\Support\Identity\IdentityNormalizer;
use App\Support\Identity\NidHasher;
use App\Support\Identity\NidKeyMissing;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3 — identity number storage and lookup security.
 *
 * Covers: keyed HMAC (never plain SHA-256), Bengali/ASCII digit convergence,
 * encryption at rest, masking, serialization hiding, and fail-closed behaviour.
 */
class NidStorageTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A deterministic 256-bit key for tests. Real keys live only in the
        // ignored local .env and are never committed.
        config()->set('privacy.nid_hmac_key', base64_encode(str_repeat('k', 32)));
        config()->set('privacy.nid_hmac_key_version', 1);
        config()->set('privacy.accepted_key_versions', [1]);
        config()->set('privacy.nid_hmac_previous_key', null);
        config()->set('privacy.fail_closed', true);
    }

    public function test_identity_number_is_encrypted_at_rest_not_stored_in_plaintext(): void
    {
        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-123456');
        $identity->save();

        $raw = $identity->getAttributes();

        $this->assertNotSame('SIM-123456', $raw['id_number_encrypted']);
        $this->assertStringNotContainsString('SIM-123456', $raw['id_number_encrypted']);
        $this->assertStringNotContainsString('SIM-123456', (string) $raw['id_number_lookup_hmac']);

        // It must still round-trip.
        $this->assertSame('SIM123456', $identity->fresh()->reveal());
    }

    public function test_lookup_uses_keyed_hmac_and_never_plain_sha256(): void
    {
        $hasher = app(NidHasher::class);
        $normalized = 'SIM123456';

        $hmac = $hasher->lookupHash('SIM-123456');

        $this->assertNotSame(
            hash('sha256', $normalized),
            $hmac,
            'NID lookup must not be a plain SHA-256 digest — that is brute-forceable'
        );
        $this->assertSame(
            hash_hmac('sha256', $normalized, base64_decode(base64_encode(str_repeat('k', 32)), true)),
            $hmac
        );
    }

    public function test_bengali_and_ascii_digits_produce_the_same_lookup_hash(): void
    {
        $hasher = app(NidHasher::class);

        // SIM-১০০২ (Bengali) must hash identically to SIM-1002 (ASCII).
        $bengali = $hasher->lookupHash('SIM-১০০২');
        $ascii = $hasher->lookupHash('SIM-1002');

        $this->assertSame($ascii, $bengali, 'Bengali and ASCII digit forms must converge');

        // Same for a full Bengali numeral string.
        $this->assertSame(
            $hasher->lookupHash('1234567890'),
            $hasher->lookupHash('১২৩৪৫৬৭৮৯০')
        );
    }

    public function test_normalizer_strips_separators_and_uppercases(): void
    {
        $this->assertSame('SIM123456', IdentityNormalizer::normalize(' sim-123 456 '));
        $this->assertSame('SIM123456', IdentityNormalizer::normalize('SIM.123_456'));
        $this->assertSame('SIM1002', IdentityNormalizer::normalize('SIM-১০০২'));
        $this->assertSame('', IdentityNormalizer::normalize(null));
    }

    public function test_normalizer_trailing_and_synthetic_detection(): void
    {
        $this->assertSame('3456', IdentityNormalizer::trailing('SIM-123456', 4));
        $this->assertSame('1002', IdentityNormalizer::trailing('SIM-১০০২', 4));
        $this->assertNull(IdentityNormalizer::trailing('   ', 4));

        $this->assertTrue(IdentityNormalizer::isSynthetic('SIM-123456'));
        $this->assertFalse(IdentityNormalizer::isSynthetic('1234567890'));
    }

    public function test_masked_number_exposes_only_the_trailing_fragment(): void
    {
        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-123456');
        $identity->save();

        $masked = $identity->maskedNumber();

        $this->assertSame('•••• 3456', $masked);
        $this->assertStringNotContainsString('SIM', $masked);
        $this->assertStringNotContainsString('123456', $masked);
    }

    public function test_encrypted_and_hmac_fields_are_hidden_from_serialization(): void
    {
        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-654321');
        $identity->save();

        $array = $identity->fresh()->toArray();
        $json = $identity->fresh()->toJson();

        $this->assertArrayNotHasKey('id_number_encrypted', $array);
        $this->assertArrayNotHasKey('id_number_lookup_hmac', $array);
        $this->assertStringNotContainsString('id_number_encrypted', $json);
        $this->assertStringNotContainsString('id_number_lookup_hmac', $json);
        $this->assertStringNotContainsString('SIM654321', $json);
    }

    public function test_identity_lookup_finds_a_record_by_its_number(): void
    {
        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-555111');
        $identity->save();

        $hashes = array_values(PartyIdentity::lookupHashesFor('SIM-555111'));
        $found = PartyIdentity::query()->whereIn('id_number_lookup_hmac', $hashes)->first();

        $this->assertNotNull($found);
        $this->assertSame($identity->id, $found->id);

        // A Bengali-digit query for the same number must also find it.
        // SIM-৫৫৫১১১ is the Bengali rendering of the same stored number.
        $bengaliHashes = array_values(PartyIdentity::lookupHashesFor('SIM-৫৫৫১১১'));
        $this->assertSame(
            $identity->id,
            PartyIdentity::query()->whereIn('id_number_lookup_hmac', $bengaliHashes)->first()->id
        );
    }

    public function test_duplicate_identity_numbers_are_rejected_by_the_unique_index(): void
    {
        $partyA = Party::factory()->create();
        $partyB = Party::factory()->create();

        $a = new PartyIdentity(['party_id' => $partyA->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $a->setIdentityNumber('SIM-DUP-01');
        $a->save();

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $b = new PartyIdentity(['party_id' => $partyB->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $b->setIdentityNumber('SIM-DUP-01');
        $b->save();
    }

    public function test_nid_operations_fail_closed_when_key_is_absent(): void
    {
        config()->set('privacy.nid_hmac_key', null);

        $this->expectException(NidKeyMissing::class);

        app(NidHasher::class)->encrypt('SIM-123456');
    }

    public function test_missing_key_does_not_break_public_demo_pages(): void
    {
        // With no NID key configured, the 12 public demo pages must still load.
        config()->set('privacy.nid_hmac_key', null);

        foreach (['dashboard', 'crypto-verify', 'ekyc-biometric', 'audit-ledger'] as $slug) {
            $this->get("/{$slug}")->assertOk();
        }

        $this->get('/login')->assertOk();
    }

    public function test_key_version_is_stamped_on_new_records(): void
    {
        config()->set('privacy.nid_hmac_key_version', 3);
        config()->set('privacy.accepted_key_versions', [3]);

        $party = Party::factory()->create();
        $identity = new PartyIdentity(['party_id' => $party->id, 'id_type' => PartyIdentity::TYPE_NID]);
        $identity->setIdentityNumber('SIM-VER-01');
        $identity->save();

        $this->assertSame(3, $identity->fresh()->key_version);
    }
}

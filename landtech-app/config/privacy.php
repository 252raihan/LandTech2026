<?php

return [

    /*
    |--------------------------------------------------------------------------
    | NID Lookup HMAC Key
    |--------------------------------------------------------------------------
    |
    | A 256-bit key, INDEPENDENT of APP_KEY, used only for the deterministic
    | HMAC-SHA256 lookup hash over normalized identity numbers.
    |
    | Why a separate key:
    |   - APP_KEY encrypts id_number_encrypted (randomized ciphertext).
    |   - NID_HMAC_KEY produces id_number_lookup_hmac (deterministic, indexable).
    | Coupling the two would mean rotating one breaks the other.
    |
    | Generate with:  php -r "echo base64_encode(random_bytes(32));"
    |
    | The value lives ONLY in the ignored local .env. It is never printed,
    | logged, staged, committed or pushed. .env.example carries a placeholder.
    |
    */

    'nid_hmac_key' => env('NID_HMAC_KEY'),

    /*
    | The OUTGOING key, present only while a rotation is in flight. Lets lookups
    | resolve rows that have not yet been re-hashed to the current version.
    | Remove it once every row reports `nid_hmac_key_version`.
    */
    'nid_hmac_previous_key' => env('NID_HMAC_PREVIOUS_KEY'),

    /*
    |--------------------------------------------------------------------------
    | HMAC Key Version
    |--------------------------------------------------------------------------
    |
    | Stamped onto every party_identities row so rotation can be staged:
    | new writes use the current version while older rows are re-hashed in the
    | background. Lookups consult every version in `accepted_key_versions`
    | until the re-hash pass completes.
    |
    | ROTATION RUNBOOK
    |   Encryption key (APP_KEY):
    |     1. Append the outgoing key to APP_PREVIOUS_KEYS. Laravel then decrypts
    |        payloads written with it while new writes use the current APP_KEY.
    |     2. Re-encrypt party_identities rows in a background pass.
    |     3. Remove the old key from APP_PREVIOUS_KEYS once no row needs it.
    |
    |   Lookup key (NID_HMAC_KEY):
    |     1. Set the new NID_HMAC_KEY and increment NID_HMAC_KEY_VERSION.
    |     2. Old and new versions are both accepted for lookup, so search keeps
    |        working during the migration window.
    |     3. Run a re-hash pass: decrypt -> normalize -> re-HMAC each row, then
    |        stamp it with the new key_version.
    |     4. Only after every row reports the current version may the previous
    |        key be retired from this config.
    |
    */

    'nid_hmac_key_version' => (int) env('NID_HMAC_KEY_VERSION', 1),

    /*
    | Key generations still honoured for lookup while a rotation is in flight.
    | Normally identical to the current version.
    */
    'accepted_key_versions' => array_values(array_filter(array_map(
        'intval',
        explode(',', (string) env('NID_HMAC_KEY_ACCEPTED_VERSIONS', '1'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Fail-Closed Behaviour
    |--------------------------------------------------------------------------
    |
    | When true (the default), any attempt to encrypt, hash, or look up an
    | identity number throws NidKeyMissing if the key is absent. This is
    | deliberately scoped to NID operations ONLY — it is NOT a boot-time check,
    | so the 12 public demo pages and all Phase 1/2 flows continue to work on a
    | machine with no key configured.
    |
    */

    'fail_closed' => (bool) env('NID_FAIL_CLOSED', true),

    /*
    |--------------------------------------------------------------------------
    | Masking
    |--------------------------------------------------------------------------
    | Number of trailing characters exposed by the masked accessor.
    */

    'mask_visible_trailing' => 4,

    'mask_character' => '•',
];

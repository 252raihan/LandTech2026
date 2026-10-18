<?php

namespace Database\Factories;

use App\Models\LandDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandDocument>
 */
class LandDocumentFactory extends Factory
{
    protected $model = LandDocument::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'land_record_id' => null,
            'document_type' => LandDocument::TYPE_KHATIAN,
            'original_name' => 'document.pdf',
            // A plausible server-generated path. Tests that need real bytes use
            // Storage::fake() and the service, not this factory.
            'stored_path' => 'land-documents/1/' . now()->format('Y/m') . '/' . $this->faker->uuid() . '.pdf',
            'disk' => LandDocument::DISK,
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'simulated'),
            'review_status' => LandDocument::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn() => ['review_status' => LandDocument::STATUS_APPROVED]);
    }
}

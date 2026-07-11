<?php

namespace Database\Factories;

use App\Models\Capture;
use App\Models\Client;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Capture>
 */
class CaptureFactory extends Factory
{
    protected $model = Capture::class;

    public function definition(): array
    {
        $capturedAt = fake()->dateTimeBetween('-1 year');

        return [
            'device_id' => Device::factory(),
            'client_id' => Client::factory(),
            'captured_at' => $capturedAt,
            'uploaded_at' => fake()->dateTimeBetween($capturedAt),
            'original_filename' => 'show tech-support all_'.fake()->word().'.txt',
            'file_path' => 'captures/'.fake()->uuid().'.txt',
            'file_hash' => fake()->unique()->sha256(),
            'file_size' => fake()->numberBetween(100_000, 5_000_000),
            'exos_version' => fake()->randomElement(['12.5.4.5', '16.2.5.4', '22.7.1.2']),
            'uptime_seconds' => fake()->numberBetween(3600, 60_000_000),
            'boot_count' => fake()->numberBetween(1, 300),
            'status' => 'completed',
        ];
    }
}

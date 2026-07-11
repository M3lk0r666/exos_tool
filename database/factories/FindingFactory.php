<?php

namespace Database\Factories;

use App\Models\Capture;
use App\Models\Device;
use App\Models\Finding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Finding>
 */
class FindingFactory extends Factory
{
    protected $model = Finding::class;

    public function definition(): array
    {
        return [
            'capture_id' => Capture::factory(),
            'device_id' => Device::factory(),
            'rule_code' => fake()->randomElement(['PORT-CRC', 'PORT-FLAP', 'SYS-REBOOT', 'ENV-TEMP', 'MEM-FREE']),
            'level' => fake()->randomElement(['critical', 'high', 'medium', 'low', 'informational']),
            'area' => fake()->randomElement(['ports', 'stability', 'environment', 'cpu_memory', 'firmware']),
            'entity' => fake()->optional()->numerify('#:##'),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'impact' => fake()->optional()->sentence(),
            'recommendation' => fake()->optional()->sentence(),
            'evidence' => fake()->optional()->text(200),
            'file_location' => 'línea '.fake()->numberBetween(1, 3000),
            'status' => 'open',
        ];
    }
}

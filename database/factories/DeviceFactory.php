<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'system_mac' => fake()->unique()->macAddress(),
            'sysname' => 'SW-'.strtoupper(fake()->unique()->lexify('????')),
            'model' => fake()->randomElement(['X440G2-48p-10G4', 'X460-48p', 'X465-24W', 'X620-16x']),
            'is_stack' => fake()->boolean(25),
            'site' => fake()->optional()->city(),
            'criticality' => fake()->randomElement(['low', 'medium', 'high']),
        ];
    }
}

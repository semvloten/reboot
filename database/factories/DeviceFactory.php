<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Device> */
class DeviceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'telefoon',
            'brand' => 'Samsung',
            'model' => 'Galaxy S23',
            'serial_number' => fake()->unique()->bothify('REBOOT-########'),
            'condition' => 'goed',
            'accessories' => 'Oplader',
            'asking_price' => '150.00',
            'photos' => [],
            'status' => 'in behandeling',
        ];
    }
}

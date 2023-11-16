<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class munuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'info'=>fake()->text(24),
            'openday'=>'1234567',
            'opentime'=>fake()->dateTime(),
            'closetime'=>fake()->dateTime(),
            'title'=>fake()->text(24),
            'img'=>fake()->imageUrl(),
            'address'=>fake()->address(),
            'api'=>fake()->url(),
            'totalpoint'=>rand(1,9)+(rand(1,9)/10),
            'countpoint'=>rand(1,999),
        ];
    }
}

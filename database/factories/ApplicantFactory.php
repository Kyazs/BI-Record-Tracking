<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Applicant>
 */
class ApplicantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name,
            'age' => $this->faker->numberBetween(18, 60),
            'address' => $this->faker->address,
            'date_of_birth' => $this->faker->date(),
            'birth_place' => $this->faker->city,
            'school' => $this->faker->company,
            'officer_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'status' => $this->faker->randomElement(['Cleared', 'Not Cleared', 'Pending']),
            'created_at' => $this->faker->dateTime(),
        ];

    }
}

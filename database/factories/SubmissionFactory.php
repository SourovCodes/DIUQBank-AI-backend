<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'section' => fake()->optional()->bothify('Sec-##'),
            'batch' => fake()->optional()->numberBetween(2018, 2035),
            'pdf_path' => 'submissions/'.fake()->uuid().'.pdf',
            'compressed_pdf_path' => null,
            'views' => fake()->numberBetween(0, 1000),
        ];
    }
}

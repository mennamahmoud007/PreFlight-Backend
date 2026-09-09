<?php

namespace Database\Factories;

use App\Models\Analysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'problem_score' => fake()->numberBetween(60, 95),
            'target_score' => fake()->numberBetween(60, 95),
            'value_score' => fake()->numberBetween(60, 95),
            'feasibility_score' => fake()->numberBetween(60, 95),
            'differentiation_score' => fake()->numberBetween(50, 95),
            'overall_score' => fake()->numberBetween(60, 95),

            'summary' => fake()->paragraph(),

            'strengths' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
            'weaknesses' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],

            'risks' => [
                [
                    'level' => 'high',
                    'description' => fake()->sentence(),
                ],
                [
                    'level' => 'medium',
                    'description' => fake()->sentence(),
                ],
                [
                    'level' => 'low',
                    'description' => fake()->sentence(),
                ],
            ],

            'primary_concern' => fake()->paragraph(),

            'critical_questions' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
            'assumptions' => [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ],
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
        ];
    }
}

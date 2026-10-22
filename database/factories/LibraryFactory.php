<?php

namespace Database\Factories;

use App\Models\Library;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Library>
 */
class LibraryFactory extends Factory
{
    protected $model = Library::class;

    public function definition(): array
    {
        return [
            'title' => fake()->catchPhrase(),
            'category' => fake()->randomElement(['Web Development', 'UI/UX Design', 'Mobile Apps', 'Data Science']),
            'file_format' => 'PDF E-Book',
            'file_size' => fake()->randomFloat(1, 1, 30) . ' MB',
            'file_path' => null,
            'cover_icon' => '📚',
            'downloads' => fake()->numberBetween(0, 100),
            'rating' => fake()->randomFloat(1, 4, 5),
        ];
    }
}

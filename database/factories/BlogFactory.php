<?php

namespace Database\Factories;

use App\Enums\BlogStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence(4, true);
        $slug = Str::slug($title);
        $date = $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d');

        return [
            'slug' => $slug,
            'source_file' => "{$date}-{$slug}.md",
            'title' => $title,
            'creator' => $this->faker->userName(),
            'excerpt' => $this->faker->sentence(12),
            'content' => "# {$title}\n\n".$this->faker->paragraphs(3, true),
            'tags' => $this->faker->randomElements(['laravel', 'php', 'react', 'inertia', 'tailwind', 'testing'], 2),
            'status' => BlogStatus::Published,
            'published_at' => $date,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => BlogStatus::Published]);
    }

    public function archived(): static
    {
        return $this->state(['status' => BlogStatus::Archived]);
    }
}

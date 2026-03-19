<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BlogViewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'blog_id' => Blog::factory(),
            'user_id' => null,
            'visitor_hash' => fake()->sha1(),
            'date' => today()->toDateString(),
        ];
    }

    public function forGuest(): static
    {
        return $this->state([
            'user_id' => null,
            'visitor_hash' => fake()->sha1(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->id,
            'visitor_hash' => null,
        ]);
    }
}

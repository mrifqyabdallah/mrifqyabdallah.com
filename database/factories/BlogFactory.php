<?php

namespace Database\Factories;

use App\Enums\BlogStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->realText(100, 5);
        $slug = Str::slug($title);
        $date = fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d');

        return [
            'slug' => $slug,
            'source_file' => "{$date}-{$slug}.md",
            'title' => $title,
            'creator' => fake()->userName(),
            'excerpt' => fake()->realText(150, 5),
            'content' => $this->generateContent($title),
            'tags' => fake()->randomElements(['laravel', 'php', 'react', 'inertia', 'tailwind', 'testing'], 2),
            'status' => BlogStatus::Published,
            'published_at' => $date,
        ];
    }

    private function generateContent(string $title): string
    {
        $blocks = [];
        $blocks[] = "# {$title}\n\n".fake()->realText();

        $available = ['paragraph', 'code', 'image', 'youtube'];
        $picks = fake()->randomElements($available, rand(3, 5), true);

        foreach ($picks as $type) {
            $blocks[] = match ($type) {
                'paragraph' => $this->paragraphBlock(),
                'code' => $this->codeBlock(),
                'image' => $this->imageBlock(),
                'youtube' => $this->youtubeBlock(),
            };
        }

        return implode("\n\n", $blocks);
    }

    private function paragraphBlock(): string
    {
        return '## '.fake()->realText(30)."\n\n".fake()->realText();
    }

    private function codeBlock(): string
    {
        $snippets = [
            ['lang' => 'php', 'code' => "public function handle(): void\n".
                "{\n".
                "    \$users = User::query()\n".
                "        ->where('active', true)\n".
                "        ->orderBy('created_at', 'desc')\n".
                "        ->get();\n".
                "\n".
                "    foreach (\$users as \$user) {\n".
                "        Mail::to(\$user)->send(new WelcomeMail(\$user));\n".
                "    }\n".
                '}',
            ],
            ['lang' => 'typescript', 'code' => "async function fetchPosts(page: number): Promise<Post[]> {\n".
                "    const response = await fetch(`/api/posts?page=\${page}`);\n".
                "    if (!response.ok) throw new Error('Failed to fetch');\n".
                "    return response.json();\n".
                '}',
            ],
            ['lang' => 'bash', 'code' => "php artisan make:model Post -mfsc\n".
                "php artisan migrate --seed\n".
                'php artisan queue:work --tries=3',
            ],
            ['lang' => 'sql', 'code' => "SELECT u.name, COUNT(p.id) AS post_count\n".
                "FROM users u\n".
                "LEFT JOIN posts p ON p.user_id = u.id\n".
                "GROUP BY u.id\n".
                "ORDER BY post_count DESC\n".
                'LIMIT 10;',
            ],
            ['lang' => 'json', 'code' => "{\n".
                "    \"name\": \"my-app\",\n".
                "    \"scripts\": {\n".
                "        \"dev\": \"vite\",\n".
                "        \"build\": \"vite build\"\n".
                "    },\n".
                "    \"dependencies\": {\n".
                "        \"react\": \"^18.0.0\"\n".
                "    }\n".
                '}',
            ],
        ];

        $snippet = $this->faker->randomElement($snippets);

        return '## '.$this->faker->realText(30)."\n\n"
            .$this->faker->realText(80)."\n\n"
            ."```{$snippet['lang']}\n{$snippet['code']}\n```";
    }

    private function imageBlock(): string
    {
        $width = fake()->randomElement([800, 1200, 1600]);
        $height = fake()->randomElement([400, 600, 800]);
        $seed = fake()->numberBetween(1, 500);
        $url = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
        $alt = fake()->realText(30);

        return '## '.fake()->realText(30)."\n\n"
            .fake()->realText(60)."\n\n"
            ."![{$alt}]({$url})";
    }

    private function youtubeBlock(): string
    {
        $ids = [
            'dQw4w9WgXcQ',
            'jNQXAC9IVRw',
            'aqz-KE-bpKQ',
            '9bZkp7q19f0',
            'kJQP7kiw5Fk',
            'fJ9rUzIMcZQ',
        ];

        $id = fake()->randomElement($ids);

        return '## '.fake()->realText(30)."\n\n"
            .fake()->realText(60)."\n\n"
            ."::youtube[{$id}]";
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

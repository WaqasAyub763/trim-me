<?php

namespace Database\Factories;

use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Link>
 */
class LinkFactory extends Factory
{
    protected $model = Link::class;

    public function definition(): array
    {
        $codes = app(ShortCodeGenerator::class);

        return [
            'original_url' => $this->faker->url(),
            'short_code'   => $codes->generate(),
            'click_count'  => 0,
            'expires_at'   => null,
            'created_at'   => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'updated_at'   => now(),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDays(rand(1, 7)),
        ]);
    }

    public function expiringSoon(): self
    {
        return $this->state(fn () => [
            'expires_at' => now()->addDays(rand(1, 7)),
        ]);
    }
}

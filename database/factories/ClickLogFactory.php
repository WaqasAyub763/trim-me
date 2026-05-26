<?php

namespace Database\Factories;

use App\Models\ClickLog;
use App\Models\Link;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClickLog>
 */
class ClickLogFactory extends Factory
{
    protected $model = ClickLog::class;

    private const REFERERS = [
        'https://news.ycombinator.com/',
        'https://twitter.com/',
        'https://github.com/laravel/laravel',
        'https://www.reddit.com/r/PHP/',
        'https://www.linkedin.com/feed/',
        'https://www.google.com/search?q=laravel',
        'https://duckduckgo.com/',
        'https://dev.to/',
        'https://stackoverflow.com/',
        'https://t.co/abc123',
        null,
        null,
    ];

    private const USER_AGENTS = [
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64; rv:126.0) Gecko/20100101 Firefox/126.0',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0',
        'curl/8.4.0',
        'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
    ];

    public function definition(): array
    {
        return [
            'link_id'    => Link::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->randomElement(self::USER_AGENTS),
            'referer'    => $this->faker->randomElement(self::REFERERS),
            'clicked_at' => $this->faker->dateTimeBetween('-14 days', 'now'),
        ];
    }

    public function forLink(Link $link): self
    {
        return $this->state(fn () => ['link_id' => $link->id]);
    }

    public function within(string $startRelative, string $endRelative = 'now'): self
    {
        return $this->state(fn () => [
            'clicked_at' => $this->faker->dateTimeBetween($startRelative, $endRelative),
        ]);
    }
}

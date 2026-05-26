<?php

namespace Database\Seeders;

use App\Models\ClickLog;
use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Five demo links with realistic destinations and a spread of states
     * (one expired, one expiring soon, three permanent), plus ~50 click
     * logs distributed across them.
     */
    public function run(): void
    {
        $codes = app(ShortCodeGenerator::class);

        $seeds = [
            [
                'original_url' => 'https://github.com/laravel/laravel/blob/10.x/README.md',
                'short_code'   => 'lvDocs',
                'expires_at'   => null,
                'weight'       => 25,
            ],
            [
                'original_url' => 'https://laravel.com/docs/10.x/eloquent',
                'short_code'   => 'elQunt',
                'expires_at'   => null,
                'weight'       => 15,
            ],
            [
                'original_url' => 'https://www.php.net/manual/en/language.types.declarations.php',
                'short_code'   => 'phpTyp',
                'expires_at'   => null,
                'weight'       => 6,
            ],
            [
                'original_url' => 'https://news.ycombinator.com/item?id=40123456',
                'short_code'   => 'hnPost',
                'expires_at'   => Carbon::now()->addDays(7),
                'weight'       => 4,
            ],
            [
                'original_url' => 'https://example.com/old-promo?utm_campaign=spring',
                'short_code'   => 'expSpr',
                'expires_at'   => Carbon::now()->subDays(3),
                'weight'       => 0,
            ],
        ];

        // Make sure codes are unique on the table — fall back to generator
        // if a manual code is already present from a prior partial run.
        $links   = [];
        $weights = [];
        foreach ($seeds as $seed) {
            $code = $seed['short_code'];
            if (Link::where('short_code', $code)->exists()) {
                $code = $codes->generate();
            }

            $link = Link::create([
                'original_url' => $seed['original_url'],
                'short_code'   => $code,
                'click_count'  => 0,
                'expires_at'   => $seed['expires_at'],
                'created_at'   => Carbon::now()->subDays(rand(7, 25)),
                'updated_at'   => Carbon::now(),
            ]);

            $links[]   = $link;
            $weights[] = $seed['weight'];
        }

        $totalWeight = (int) array_sum($weights);
        $totalClicks = 50;

        foreach ($links as $i => $link) {
            $weight = $weights[$i] ?? 0;
            if ($weight === 0 || $totalWeight === 0) {
                continue;
            }

            $count = (int) round(($weight / $totalWeight) * $totalClicks);
            if ($count <= 0) {
                continue;
            }

            ClickLog::factory()
                ->count($count)
                ->forLink($link)
                ->create();

            // Keep click_count consistent with the actual log rows.
            $link->update(['click_count' => $count]);
        }
    }
}

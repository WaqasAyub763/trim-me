<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Verifies that {@see ShortCodeGenerator} retries when its first candidate
 * collides with an existing row, and that the persisted code is unique.
 */
class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generator_retries_on_collision_and_persists_a_unique_code(): void
    {
        Link::factory()->create(['short_code' => 'COLIDE']);

        $mock = Mockery::mock(ShortCodeGenerator::class)->makePartial();
        $mock->shouldReceive('randomCode')
            ->with(ShortCodeGenerator::LENGTH)
            ->andReturn('COLIDE', 'COLIDE', 'UNIQUE');

        $this->app->instance(ShortCodeGenerator::class, $mock);

        $response = $this->post(route('links.store'), [
            'original_url' => 'https://example.com',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('links', 2);
        $this->assertDatabaseHas('links', [
            'short_code'   => 'UNIQUE',
            'original_url' => 'https://example.com',
        ]);
    }

    public function test_generator_throws_after_exhausting_attempts(): void
    {
        Link::factory()->create(['short_code' => 'STUCKK']);

        $mock = Mockery::mock(ShortCodeGenerator::class)->makePartial();
        $mock->shouldReceive('randomCode')
            ->with(ShortCodeGenerator::LENGTH)
            ->andReturn('STUCKK');

        $this->expectException(\RuntimeException::class);

        $mock->generate();
    }

    public function test_random_code_uses_only_base62_alphabet(): void
    {
        $generator = new ShortCodeGenerator();

        for ($i = 0; $i < 50; $i++) {
            $code = $generator->randomCode(6);
            $this->assertSame(6, strlen($code));
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{6}$/', $code);
        }
    }

    public function test_short_code_column_has_unique_constraint(): void
    {
        Link::factory()->create(['short_code' => 'unique']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Link::factory()->create(['short_code' => 'unique']);
    }
}

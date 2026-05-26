<?php

namespace Tests\Feature;

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders(): void
    {
        $response = $this->get(route('links.create'));

        $response->assertOk();
        $response->assertSee('Create a short link');
        $response->assertSee('Destination URL');
    }

    public function test_a_valid_url_creates_a_link_and_redirects_to_result(): void
    {
        $response = $this->post(route('links.store'), [
            'original_url' => 'https://github.com/laravel/laravel',
        ]);

        $this->assertDatabaseCount('links', 1);

        $link = Link::firstOrFail();

        $this->assertSame('https://github.com/laravel/laravel', $link->original_url);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{6}$/', $link->short_code);
        $this->assertNull($link->expires_at);
        $this->assertSame(0, $link->click_count);

        $response->assertRedirect(route('links.show', $link->short_code));
    }

    public function test_result_page_displays_the_short_code(): void
    {
        $link = Link::factory()->create([
            'original_url' => 'https://laravel.com/docs',
            'short_code'   => 'aB3xQ9',
        ]);

        $response = $this->get(route('links.show', $link->short_code));

        $response->assertOk();
        $response->assertSee('aB3xQ9');
        $response->assertSee('https://laravel.com/docs');
    }

    public function test_an_invalid_url_is_rejected_with_validation_errors(): void
    {
        $response = $this->from(route('links.create'))
            ->post(route('links.store'), [
                'original_url' => 'not-a-url',
            ]);

        $response->assertRedirect(route('links.create'));
        $response->assertSessionHasErrors('original_url');
        $this->assertDatabaseCount('links', 0);
    }

    public function test_a_url_longer_than_2048_chars_is_rejected(): void
    {
        $longUrl = 'https://example.com/' . str_repeat('a', 2050);

        $response = $this->from(route('links.create'))
            ->post(route('links.store'), ['original_url' => $longUrl]);

        $response->assertSessionHasErrors('original_url');
        $this->assertDatabaseCount('links', 0);
    }

    public function test_expiry_in_the_past_is_rejected(): void
    {
        $response = $this->from(route('links.create'))
            ->post(route('links.store'), [
                'original_url' => 'https://example.com',
                'expires_at'   => now()->subDay()->format('Y-m-d\TH:i'),
            ]);

        $response->assertSessionHasErrors('expires_at');
        $this->assertDatabaseCount('links', 0);
    }
}

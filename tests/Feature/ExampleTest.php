<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_landing_page_renders_real_navigation_links(): void
    {
        $response = $this->get(route('home'));

        $response
            ->assertOk()
            ->assertDontSee('{{ url', false)
            ->assertDontSee('href="#"', false)
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('src="'.asset('assets/images/branding/smartlms-logo.webp').'"', false)
            ->assertSee('href="'.asset('favicon.ico').'"', false);

        foreach ([
            'assets/images/branding/smartlms-logo.webp',
            'assets/images/branding/smartlms-logo-header.webp',
            'assets/images/branding/chatbot-mascot.webp',
            'assets/images/ui/dashboard-learning.webp',
            'favicon.ico',
        ] as $asset) {
            $this->assertFileExists(public_path($asset));
            $this->assertGreaterThan(0, filesize(public_path($asset)));
        }
    }
}

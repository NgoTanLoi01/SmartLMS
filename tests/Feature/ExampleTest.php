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

    public function test_public_marketing_pages_are_indexable_and_internally_linked(): void
    {
        foreach (config('marketing.pages') as $slug => $page) {
            $response = $this->get(route($page['route']));

            $response
                ->assertOk()
                ->assertSee('<title>'.$page['title'].'</title>', false)
                ->assertSee('content="index, follow,', false)
                ->assertSee('rel="canonical"', false)
                ->assertSee($page['headline'])
                ->assertSee('SmartLMS.io.vn')
                ->assertSee(route('marketing.about'), false);
        }
    }

    public function test_sitemap_and_robots_expose_all_public_marketing_pages(): void
    {
        $sitemap = file_get_contents(public_path('sitemap.xml'));
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap: https://smartlms.io.vn/sitemap.xml', $robots);
        $this->assertStringContainsString('<loc>https://smartlms.io.vn/</loc>', $sitemap);

        foreach (array_keys(config('marketing.pages')) as $slug) {
            $this->assertStringContainsString("<loc>https://smartlms.io.vn/{$slug}</loc>", $sitemap);
        }
    }
}

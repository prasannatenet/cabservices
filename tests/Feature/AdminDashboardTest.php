<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_balanced_markup()
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        $html = $response->getContent();

        $this->assertSame(
            substr_count($html, '<div'),
            substr_count($html, '</div>'),
            'The dashboard must not leave a <div> unclosed.'
        );

        $this->assertSame(
            substr_count($html, '<table'),
            substr_count($html, '</table>'),
            'The dashboard must not leave a <table> unclosed.'
        );
    }

    public function test_dashboard_does_not_stack_its_own_page_padding()
    {
        $admin = User::factory()->create();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

        // The layout already gives the content its padding, so the view must not
        // wrap it in a second padded container.
        $this->assertStringNotContainsString('class="py-12"', $html);

        // The layout no longer caps the content width: tables and cards must be
        // able to fill the main content area. The view must not re-introduce a
        // centred, width-capped wrapper of its own either.
        $this->assertStringNotContainsString('max-w-7xl mx-auto', $html);
    }

    public function test_admin_tables_fill_the_main_content_width()
    {
        $admin = User::factory()->create();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->getContent();

        // The layout main must span the full width available next to the sidebar.
        $this->assertMatchesRegularExpression(
            '/<main class="[^"]*\bw-full\b[^"]*">/',
            $html,
            'The admin main content area must be full width.'
        );

        // Tables are styled through the shared .admin-table component so every
        // listing in the panel looks the same and stretches to the card edges.
        $this->assertStringContainsString('<table class="admin-table">', $html);
        $this->assertStringContainsString('class="admin-table-scroll"', $html);
    }
}

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

        // The layout already centres the content and gives it its padding, so
        // the view must not wrap it in a second centred, padded container.
        $this->assertStringNotContainsString('class="py-12"', $html);

        // The layout centres the page twice: the page heading and the content
        // below it. The view must not add a third.
        $this->assertSame(
            2,
            substr_count($html, 'max-w-7xl mx-auto'),
            'Only the layout may centre the page content.'
        );
    }
}

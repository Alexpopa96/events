<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteRequestBrowseTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(Category $category, string $status, string $message): QuoteRequest
    {
        return QuoteRequest::create([
            'category_id' => $category->id,
            'name' => 'Ana Popescu',
            'email' => 'ana@example.com',
            'message' => $message,
            'status' => $status,
        ]);
    }

    public function test_guests_see_only_open_requests_without_contact_data(): void
    {
        $category = Category::create(['name' => 'Fotografi', 'slug' => 'fotografi', 'is_active' => true]);
        $this->makeRequest($category, 'open', 'Caut fotograf.');
        $this->makeRequest($category, 'pending_review', 'Încă în moderare.');
        $this->makeRequest($category, 'closed', 'Deja închisă.');

        $this->get(route('quote-requests.browse'))
            ->assertOk()
            ->assertDontSee('ana@example.com')
            ->assertInertia(fn (Assert $page) => $page
                ->component('QuoteRequests/Browse')
                ->where('openCount', 1)
                ->has('quoteRequests.data', 1)
                ->where('quoteRequests.data.0.message', 'Caut fotograf.')
                ->missing('quoteRequests.data.0.email')
                ->missing('quoteRequests.data.0.name')
            );
    }

    public function test_requests_can_be_filtered_by_category(): void
    {
        $photo = Category::create(['name' => 'Fotografi', 'slug' => 'fotografi', 'is_active' => true]);
        $music = Category::create(['name' => 'Formații', 'slug' => 'formatii', 'is_active' => true]);
        $this->makeRequest($photo, 'open', 'Caut fotograf.');
        $this->makeRequest($music, 'open', 'Caut formație.');

        $this->get(route('quote-requests.browse', ['category' => 'formatii']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('quoteRequests.data', 1)
                ->where('quoteRequests.data.0.category', 'Formații')
                ->where('filters.category', 'formatii')
            );
    }
}

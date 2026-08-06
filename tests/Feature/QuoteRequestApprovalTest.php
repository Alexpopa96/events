<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\QuoteRequest;
use App\Models\User;
use Database\Seeders\PermmisionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class QuoteRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteRequest(string $status = 'pending_review'): QuoteRequest
    {
        $category = Category::create(['name' => 'Fotografie', 'slug' => 'fotografie']);
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('client');

        return QuoteRequest::create([
            'category_id' => $category->id,
            'title' => 'Caut fotograf pentru nuntă',
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0700000000',
            'message' => 'Căutăm un fotograf profesionist.',
            'status' => $status,
        ]);
    }

    public function test_submitting_a_quote_request_creates_it_as_pending_review(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $category = Category::create(['name' => 'Fotografie', 'slug' => 'fotografie']);
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('client');

        $response = $this->actingAs($user)->post(route('quote-requests.store'), [
            'category_id' => $category->id,
            'title' => 'Caut fotograf pentru nuntă',
            'message' => 'Căutăm un fotograf profesionist.',
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0700000000',
        ]);

        $quoteRequest = QuoteRequest::firstOrFail();

        $response->assertRedirect(route('quote-requests.success', $quoteRequest));
        $this->assertSame('pending_review', $quoteRequest->status);
    }

    public function test_owner_can_view_their_success_page(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $quoteRequest = $this->makeQuoteRequest();

        $response = $this->actingAs($quoteRequest->user)->get(route('quote-requests.success', $quoteRequest));

        $response->assertOk();
    }

    public function test_other_user_cannot_view_someone_elses_success_page(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $quoteRequest = $this->makeQuoteRequest();
        $otherUser = User::factory()->create(['status' => true]);
        $otherUser->assignRole('client');

        $response = $this->actingAs($otherUser)->get(route('quote-requests.success', $quoteRequest));

        $response->assertForbidden();
    }

    public function test_admin_can_approve_a_pending_quote_request(): void
    {
        Notification::fake();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $quoteRequest = $this->makeQuoteRequest();
        $admin = User::factory()->create(['status' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('administration.quote-requests.approve', $quoteRequest));

        $response->assertRedirect();

        $this->assertSame('open', $quoteRequest->fresh()->status);
        $this->assertNotNull($quoteRequest->fresh()->approved_at);

        Notification::assertSentTo($quoteRequest->user, \App\Notifications\QuoteRequestApproved::class);
    }

    public function test_admin_can_reject_a_pending_quote_request(): void
    {
        Notification::fake();

        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $quoteRequest = $this->makeQuoteRequest();
        $admin = User::factory()->create(['status' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('administration.quote-requests.reject', $quoteRequest), [
                'reason' => 'Informații insuficiente.',
            ]);

        $response->assertRedirect();

        $this->assertSame('rejected', $quoteRequest->fresh()->status);
        $this->assertSame('Informații insuficiente.', $quoteRequest->fresh()->rejection_reason);

        Notification::assertSentTo($quoteRequest->user, \App\Notifications\QuoteRequestRejected::class);
    }

    public function test_non_admin_cannot_approve_a_quote_request(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermmisionSeeder::class);

        $quoteRequest = $this->makeQuoteRequest();
        $client = User::factory()->create(['status' => true]);
        $client->assignRole('client');

        $response = $this->actingAs($client)
            ->post(route('administration.quote-requests.approve', $quoteRequest));

        $response->assertForbidden();
        $this->assertSame('pending_review', $quoteRequest->fresh()->status);
    }
}

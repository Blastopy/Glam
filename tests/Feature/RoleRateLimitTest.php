<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_requests_are_rate_limited_by_role(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
        ]);

        $this->actingAs($user, 'sanctum');

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->getJson('/api/v1/me')
                ->assertOk();
        }

        $this->getJson('/api/v1/me')
            ->assertStatus(429)
            ->assertJson([
                'message' => 'Too many attempts. Please wait a moment and try again.',
            ]);
    }
}

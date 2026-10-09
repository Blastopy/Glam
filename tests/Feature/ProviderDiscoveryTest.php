<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_provider_profiles_are_hidden_from_public_discovery(): void
    {
        SystemSetting::current()->update(['payments_enabled' => true]);

        $providerUser = User::factory()->create([
            'role' => 'provider',
        ]);

        $providerProfile = ProviderProfile::create([
            'user_id' => $providerUser->id,
            'business_name' => 'Style Studio',
            'provider_type' => 'barber',
            'is_open' => true,
            'subscription_expires_at' => now()->addDays(30),
        ]);

        $providerUser->delete();

        $response = $this->getJson('/api/v1/providers');

        $response->assertOk();
        $this->assertNotContains($providerProfile->id, array_column($response->json('data'), 'id'));
    }
}

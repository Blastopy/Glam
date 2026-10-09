<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ProviderProfile;
use App\Models\Service;
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

    public function test_pending_appointments_mark_a_suspended_provider(): void
    {
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

        $service = Service::create([
            'provider_profile_id' => $providerProfile->id,
            'name' => 'Classic cut',
            'description' => 'Test service',
            'price' => 1200,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $client = User::factory()->create([
            'role' => 'client',
        ]);

        $appointment = Appointment::create([
            'client_id' => $client->id,
            'provider_profile_id' => $providerProfile->id,
            'service_id' => $service->id,
            'scheduled_at' => now()->addDays(2),
            'status' => 'pending',
            'price_at_booking' => 1200,
        ]);

        $providerUser->delete();

        $response = $this->actingAs($client, 'sanctum')
            ->getJson('/api/v1/appointments?status=pending');

        $response->assertOk();
        $this->assertTrue($response->json('data.0.provider_suspended'));
        $this->assertSame('This provider account has been suspended.', $response->json('data.0.provider_suspension_message'));
        $this->assertSame('pending', $appointment->fresh()->status);
    }
}

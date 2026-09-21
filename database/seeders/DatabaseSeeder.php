<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Message;
use App\Models\ProviderProfile;
use App\Models\ProviderSubscriptionPayment;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Every seeded account shares this password so it's easy to log in and demo the app. */
    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->seedAdmin();
        $providers = $this->seedProviders();
        $clients = $this->seedClients();
        $this->seedAppointmentsReviewsAndMessages($providers, $clients);

        $this->command->newLine();
        $this->command->info('Seeded demo data — every account\'s password is "' . self::PASSWORD . '"');
        $this->command->line('  Admin:    admin@salonbook.co.ke');
        $this->command->line('  Provider: david.mwangi@example.com   (active, verified, featured)');
        $this->command->line('  Provider: peter.otieno@example.com   (EXPIRED subscription — demo /provider/activate)');
        $this->command->line('  Provider: amina.hassan@example.com   (active, unverified)');
        $this->command->line('  Client:   john.kamau@example.com     (has a completed booking, review, and a message thread with David)');
    }

    private function seedAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@salonbook.co.ke'],
            [
                'name' => 'Jane Admin',
                'phone' => '254700000001',
                'password' => Hash::make(self::PASSWORD),
                'role' => 'admin',
            ]
        );
    }

    /** @return array<string, array{user: User, profile: ProviderProfile}> keyed by business name */
    private function seedProviders(): array
    {
        $definitions = [
            [
                'owner' => ['name' => 'David Mwangi', 'email' => 'david.mwangi@example.com', 'phone' => '254711000001'],
                'business_name' => 'Fresh Cuts Barbershop',
                'provider_type' => 'barber',
                'bio' => 'Sharp fades and clean lines in the heart of the CBD.',
                'address' => 'Moi Avenue, Nairobi CBD',
                'lat' => -1.2864, 'lng' => 36.8172,
                'is_verified' => true, 'is_featured' => true, 'is_open' => true,
                'subscription_days' => 25,
                'services' => [
                    ['name' => 'Skin Fade', 'description' => 'Clean fade with a sharp line-up.', 'price' => 300, 'duration_minutes' => 30],
                    ['name' => 'Classic Haircut', 'description' => null, 'price' => 250, 'duration_minutes' => 30],
                    ['name' => 'Beard Trim', 'description' => null, 'price' => 150, 'duration_minutes' => 15],
                    ['name' => 'Hot Towel Shave', 'description' => null, 'price' => 200, 'duration_minutes' => 20],
                    ['name' => 'Kids Cut', 'description' => 'For under-12s.', 'price' => 200, 'duration_minutes' => 20],
                ],
            ],
            [
                'owner' => ['name' => 'Grace Wanjiru', 'email' => 'grace.wanjiru@example.com', 'phone' => '254711000002'],
                'business_name' => 'Glow Beauty Salon',
                'provider_type' => 'salon',
                'bio' => 'Braids, weaves, and treatments for every hair type.',
                'address' => 'Woodvale Grove, Westlands',
                'lat' => -1.2647, 'lng' => 36.8035,
                'is_verified' => true, 'is_featured' => false, 'is_open' => true,
                'subscription_days' => 18,
                'services' => [
                    ['name' => 'Box Braids', 'description' => 'Medium size, shoulder length.', 'price' => 2500, 'duration_minutes' => 180],
                    ['name' => 'Cornrows', 'description' => null, 'price' => 1200, 'duration_minutes' => 90],
                    ['name' => 'Silk Press', 'description' => null, 'price' => 1500, 'duration_minutes' => 60],
                    ['name' => 'Full Weave', 'description' => null, 'price' => 3000, 'duration_minutes' => 240],
                    ['name' => 'Treatment & Blow Dry', 'description' => null, 'price' => 800, 'duration_minutes' => 45],
                ],
            ],
            [
                'owner' => ['name' => 'Amina Hassan', 'email' => 'amina.hassan@example.com', 'phone' => '254711000003'],
                'business_name' => 'Nails by Amina',
                'provider_type' => 'manicurist',
                'bio' => 'Gel, acrylic, and nail art — home studio in Kilimani.',
                'address' => 'Argwings Kodhek Road, Kilimani',
                'lat' => -1.2921, 'lng' => 36.7833,
                'is_verified' => false, 'is_featured' => false, 'is_open' => true,
                'subscription_days' => 9,
                'services' => [
                    ['name' => 'Gel Manicure', 'description' => null, 'price' => 800, 'duration_minutes' => 45],
                    ['name' => 'Classic Pedicure', 'description' => null, 'price' => 600, 'duration_minutes' => 40],
                    ['name' => 'Nail Art (per nail)', 'description' => null, 'price' => 100, 'duration_minutes' => 10],
                    ['name' => 'Acrylic Full Set', 'description' => null, 'price' => 1800, 'duration_minutes' => 90],
                ],
            ],
            [
                'owner' => ['name' => 'Peter Otieno', 'email' => 'peter.otieno@example.com', 'phone' => '254711000004'],
                'business_name' => 'Kasarani Kings Barber',
                'provider_type' => 'barber',
                'bio' => 'Neighbourhood barbershop, walk-ins welcome.',
                'address' => 'Mwiki Road, Kasarani',
                'lat' => -1.2199, 'lng' => 36.8971,
                'is_verified' => false, 'is_featured' => false, 'is_open' => false,
                'subscription_days' => -5, // expired — demonstrates the /provider/activate flow and discovery hiding
                'services' => [
                    ['name' => 'Skin Fade', 'description' => null, 'price' => 250, 'duration_minutes' => 30],
                    ['name' => 'Beard Trim', 'description' => null, 'price' => 100, 'duration_minutes' => 15],
                ],
            ],
            [
                'owner' => ['name' => 'Susan Njeri', 'email' => 'susan.njeri@example.com', 'phone' => '254711000005'],
                'business_name' => 'Karen Hair Studio',
                'provider_type' => 'salon',
                'bio' => 'Premium hair care in a quiet, relaxed setting.',
                'address' => 'Karen Road, Karen',
                'lat' => -1.3197, 'lng' => 36.7076,
                'is_verified' => true, 'is_featured' => true, 'is_open' => true,
                'subscription_days' => 29,
                'services' => [
                    ['name' => 'Silk Press', 'description' => null, 'price' => 1600, 'duration_minutes' => 60],
                    ['name' => 'Deep Conditioning', 'description' => null, 'price' => 900, 'duration_minutes' => 40],
                    ['name' => 'Kids Braids', 'description' => null, 'price' => 1500, 'duration_minutes' => 90],
                ],
            ],
        ];

        $providers = [];

        foreach ($definitions as $def) {
            $user = User::updateOrCreate(
                ['email' => $def['owner']['email']],
                [
                    'name' => $def['owner']['name'],
                    'phone' => $def['owner']['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'provider',
                ]
            );

            $expiresAt = now()->addDays($def['subscription_days']);

            $profile = ProviderProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $def['business_name'],
                    'provider_type' => $def['provider_type'],
                    'bio' => $def['bio'],
                    'address' => $def['address'],
                    'latitude' => $def['lat'],
                    'longitude' => $def['lng'],
                    'is_open' => $def['is_open'],
                    'is_verified' => $def['is_verified'],
                    'is_featured' => $def['is_featured'],
                    'subscription_expires_at' => $expiresAt,
                ]
            );

            foreach ($def['services'] as $s) {
                Service::updateOrCreate(
                    ['provider_profile_id' => $profile->id, 'name' => $s['name']],
                    [
                        'description' => $s['description'],
                        'price' => $s['price'],
                        'duration_minutes' => $s['duration_minutes'],
                        'is_active' => true,
                    ]
                );
            }

            // A payment record explaining how they got their current expiry — even
            // the expired provider had one, matching their now-lapsed 30-day period.
            $periodEnd = $expiresAt;
            $periodStart = $periodEnd->copy()->subDays(30);

            ProviderSubscriptionPayment::updateOrCreate(
                ['provider_profile_id' => $profile->id, 'period_end' => $periodEnd],
                [
                    'amount' => 70,
                    'phone' => $def['owner']['phone'],
                    'mpesa_receipt_number' => strtoupper(uniqid('SBK')),
                    'status' => 'successful',
                    'result_code' => 0,
                    'result_desc' => 'The service request is processed successfully.',
                    'paid_at' => $periodStart,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]
            );

            $providers[$def['business_name']] = ['user' => $user, 'profile' => $profile];
        }

        return $providers;
    }

    /** @return array<string, User> keyed by name */
    private function seedClients(): array
    {
        $definitions = [
            ['name' => 'John Kamau', 'email' => 'john.kamau@example.com', 'phone' => '254722000001'],
            ['name' => 'Mary Achieng', 'email' => 'mary.achieng@example.com', 'phone' => '254722000002'],
            ['name' => 'Brian Kiprop', 'email' => 'brian.kiprop@example.com', 'phone' => '254722000003'],
            ['name' => 'Faith Nyambura', 'email' => 'faith.nyambura@example.com', 'phone' => '254722000004'],
        ];

        $clients = [];

        foreach ($definitions as $def) {
            $clients[$def['name']] = User::updateOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'phone' => $def['phone'],
                    'password' => Hash::make(self::PASSWORD),
                    'role' => 'client',
                ]
            );
        }

        return $clients;
    }

    private function seedAppointmentsReviewsAndMessages(array $providers, array $clients): void
    {
        $freshCuts = $providers['Fresh Cuts Barbershop']['profile'];
        $glow = $providers['Glow Beauty Salon']['profile'];
        $amina = $providers['Nails by Amina']['profile'];
        $karen = $providers['Karen Hair Studio']['profile'];

        $service = fn (ProviderProfile $p, string $name) => Service::where('provider_profile_id', $p->id)->where('name', $name)->first();

        $john = $clients['John Kamau'];
        $mary = $clients['Mary Achieng'];
        $brian = $clients['Brian Kiprop'];
        $faith = $clients['Faith Nyambura'];

        // Upcoming, confirmed
        $this->makeAppointment($john, $freshCuts, $service($freshCuts, 'Skin Fade'), now()->addDay()->setTime(10, 0), 'confirmed');
        $this->makeAppointment($brian, $freshCuts, $service($freshCuts, 'Beard Trim'), now()->addDays(3)->setTime(14, 0), 'confirmed');

        // Upcoming, still awaiting provider confirmation
        $this->makeAppointment($mary, $amina, $service($amina, 'Gel Manicure'), now()->addDays(2)->setTime(11, 0), 'pending');
        $this->makeAppointment($faith, $glow, $service($glow, 'Cornrows'), now()->addDay()->setTime(9, 0), 'pending');

        // Completed, with reviews — also adds the client to the provider's book
        $this->completeWithReview($john, $freshCuts, $service($freshCuts, 'Classic Haircut'), now()->subDays(10), 5, 'Quick, clean fade every time. My go-to barber.');
        $this->completeWithReview($mary, $glow, $service($glow, 'Box Braids'), now()->subDays(5), 4, 'Lovely work, took a bit longer than expected.');
        $this->completeWithReview($brian, $karen, $service($karen, 'Silk Press'), now()->subDays(20), 5, 'Hair felt amazing for weeks. Highly recommend.');

        // Cancelled
        $this->makeAppointment($faith, $freshCuts, $service($freshCuts, 'Skin Fade'), now()->subDays(2)->setTime(15, 0), 'cancelled');

        // A short message thread between John and Fresh Cuts' owner
        $this->seedMessageThread($john, $providers['Fresh Cuts Barbershop']['user']);
    }

    private function makeAppointment(User $client, ProviderProfile $profile, ?Service $service, $scheduledAt, string $status): ?Appointment
    {
        if (! $service) {
            return null; // service lookup failed — skip rather than crash the whole seeder
        }

        return Appointment::updateOrCreate(
            [
                'client_id' => $client->id,
                'provider_profile_id' => $profile->id,
                'service_id' => $service->id,
                'scheduled_at' => $scheduledAt,
            ],
            [
                'price_at_booking' => $service->price,
                'status' => $status,
            ]
        );
    }

    private function completeWithReview(User $client, ProviderProfile $profile, ?Service $service, $scheduledAt, int $rating, string $comment): void
    {
        $appointment = $this->makeAppointment($client, $profile, $service, $scheduledAt, 'completed');

        if (! $appointment) {
            return;
        }

        Review::updateOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'client_id' => $client->id,
                'provider_profile_id' => $profile->id,
                'rating' => $rating,
                'comment' => $comment,
            ]
        );

        $profile->clients()->syncWithoutDetaching([$client->id => ['source' => 'auto_after_booking']]);

        // Recalculate the cached rating, same as ReviewController does on a real review submission.
        $profile->update([
            'average_rating' => $profile->reviews()->avg('rating') ?? 0,
            'reviews_count' => $profile->reviews()->count(),
            'completed_appointments_count' => $profile->appointments()->where('status', 'completed')->count(),
        ]);
    }

    private function seedMessageThread(User $client, User $provider): void
    {
        $thread = [
            ['from' => $client, 'to' => $provider, 'body' => 'Hi! Do you have any slots open this Saturday afternoon?', 'read' => true],
            ['from' => $provider, 'to' => $client, 'body' => 'Yes, 2pm or 4pm both work — which suits you?', 'read' => true],
            ['from' => $client, 'to' => $provider, 'body' => '2pm is perfect, thank you!', 'read' => false],
        ];

        foreach ($thread as $m) {
            Message::updateOrCreate(
                ['sender_id' => $m['from']->id, 'receiver_id' => $m['to']->id, 'body' => $m['body']],
                ['read_at' => $m['read'] ? now() : null]
            );
        }
    }
}

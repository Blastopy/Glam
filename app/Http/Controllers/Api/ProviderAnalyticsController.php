<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProviderAnalyticsController extends Controller
{
    /**
     * Client-focused stats for the provider's own dashboard: how many clients
     * they have, how fast that's growing, and how many come back for a
     * second visit — the things that actually tell a provider whether their
     * business is healthy, beyond just the raw booking list.
     */
    public function clients(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403, 'Only providers have client analytics');

        $totalClients = $profile->clients()->count();

        $newClientsThisMonth = $profile->clients()
            ->wherePivot('created_at', '>=', now()->startOfMonth())
            ->count();

        // A "repeat" client is one with more than one completed appointment
        // with this specific provider — not just present in the client book,
        // which can also happen from a single completed visit or a manual add.
        $repeatClientIds = $profile->appointments()
            ->where('status', 'completed')
            ->select('client_id', DB::raw('COUNT(*) as visits'))
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('client_id');

        $completedAppointments = $profile->appointments()->where('status', 'completed')->count();

        $days = min((int) $request->input('days', 30), 180);
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = $profile->clients()
            ->wherePivot('created_at', '>=', $start)
            ->selectRaw('DATE(client_provider.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $labels = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());
        $values = $labels->map(fn ($date) => (int) $rows->first(fn ($r) => $r->date === $date)?->count ?? 0);

        return response()->json([
            'total_clients' => $totalClients,
            'new_clients_this_month' => $newClientsThisMonth,
            'repeat_clients' => $repeatClientIds->count(),
            'repeat_rate' => $totalClients > 0 ? round($repeatClientIds->count() / $totalClients, 2) : 0,
            'completed_appointments' => $completedAppointments,
            'average_rating' => (float) $profile->average_rating,
            'reviews_count' => $profile->reviews_count,
            'new_clients_series' => [
                'labels' => $labels->values(),
                'values' => $values->values(),
            ],
        ]);
    }
}

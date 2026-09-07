<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\ProviderProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminAnalyticsController extends Controller
{
    /** High-level counters for the top of the admin dashboard. */
    public function overview()
    {
        return response()->json([
            'clients' => User::where('role', 'client')->count(),
            'providers' => User::where('role', 'provider')->count(),
            'verified_providers' => ProviderProfile::where('is_verified', true)->count(),
            'open_providers' => ProviderProfile::where('is_open', true)->count(),
            'appointments_total' => Appointment::count(),
            'appointments_this_week' => Appointment::where('created_at', '>=', now()->subDays(7))->count(),
            'completed_appointments' => Appointment::where('status', 'completed')->count(),
            'cancelled_appointments' => Appointment::whereIn('status', ['cancelled', 'no_show'])->count(),
            'total_revenue' => (float) Payment::where('status', 'successful')->sum('amount'),
            'revenue_this_month' => (float) Payment::where('status', 'successful')
                ->where('paid_at', '>=', now()->startOfMonth())
                ->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'appointment_status_breakdown' => Appointment::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
        ]);
    }

    /** Daily successful-deposit revenue for the last N days — feeds a line chart. */
    public function revenueSeries(Request $request)
    {
        $days = min((int) $request->input('days', 30), 180);
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = Payment::where('status', 'successful')
            ->where('paid_at', '>=', $start)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        [$labels, $values] = $this->fillDailySeries($start, $days, $rows, 'total');

        return response()->json(['labels' => $labels, 'values' => $values]);
    }

    /** Daily booking counts for the last N days, split by status — feeds a stacked bar chart. */
    public function bookingsSeries(Request $request)
    {
        $days = min((int) $request->input('days', 30), 180);
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = Appointment::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->get();

        $labels = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());

        $statuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
        $series = collect($statuses)->mapWithKeys(function ($status) use ($rows, $labels) {
            $values = $labels->map(function ($date) use ($rows, $status) {
                return (int) $rows->first(fn ($r) => $r->date === $date && $r->status === $status)?->count ?? 0;
            });

            return [$status => $values->values()];
        });

        return response()->json(['labels' => $labels->values(), 'series' => $series]);
    }

    /** Signups per day for the last N days, split client vs provider — feeds a line chart. */
    public function userGrowthSeries(Request $request)
    {
        $days = min((int) $request->input('days', 30), 180);
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = User::where('created_at', '>=', $start)
            ->whereIn('role', ['client', 'provider'])
            ->selectRaw('DATE(created_at) as date, role, COUNT(*) as count')
            ->groupBy('date', 'role')
            ->get();

        $labels = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());

        $clients = $labels->map(fn ($date) => (int) $rows->first(fn ($r) => $r->date === $date && $r->role === 'client')?->count ?? 0);
        $providers = $labels->map(fn ($date) => (int) $rows->first(fn ($r) => $r->date === $date && $r->role === 'provider')?->count ?? 0);

        return response()->json([
            'labels' => $labels->values(),
            'clients' => $clients->values(),
            'providers' => $providers->values(),
        ]);
    }

    /** Top 10 providers by completed bookings — feeds a leaderboard / horizontal bar chart. */
    public function topProviders()
    {
        $providers = ProviderProfile::query()
            ->withCount(['appointments as completed_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('completed_count')
            ->limit(10)
            ->get(['id', 'business_name', 'provider_type', 'average_rating', 'reviews_count']);

        return response()->json($providers);
    }

    /**
     * Fills any missing dates in a raw grouped-by-date result with zero, so the
     * frontend chart never has to guess at gaps.
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function fillDailySeries(Carbon $start, int $days, Collection $rowsByDate, string $column): array
    {
        $labels = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());

        $values = $labels->map(fn ($date) => (float) ($rowsByDate->get($date)->{$column} ?? 0));

        return [$labels->values(), $values->values()];
    }
}

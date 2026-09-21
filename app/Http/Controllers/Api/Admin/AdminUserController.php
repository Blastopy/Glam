<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->withTrashed(); // admins need to see suspended accounts too

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('phone', 'like', $term));
        }

        return response()->json(
            $query->latest()->paginate(25)
        );
    }

    public function show(Request $request, int $id)
    {
        $user = User::withTrashed()
            ->with('providerProfile')
            ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Suspends an account via soft delete. Laravel's SoftDeletes global scope means
     * a suspended user simply stops matching normal login lookups — no separate flag needed.
     */
    public function suspend(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        abort_if($user->role === 'admin', 422, 'Cannot suspend another admin account');

        $user->delete();

        return response()->json(['message' => 'Account suspended']);
    }

    public function reactivate(Request $request, int $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['message' => 'Account reactivated']);
    }
}

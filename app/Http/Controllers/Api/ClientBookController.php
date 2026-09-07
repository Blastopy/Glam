<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ClientBookController extends Controller
{
    /** Feature #7: provider views their managed client list. */
    public function index(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403);

        $clients = $profile->clients()
            ->select('users.id', 'users.name', 'users.phone', 'users.avatar')
            ->paginate(30);

        return response()->json($clients);
    }

    /** Provider manually adds a client (e.g. a walk-in, or "add upon request"). */
    public function store(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
            'source' => 'sometimes|in:manual_add,client_request',
        ]);

        $profile->clients()->syncWithoutDetaching([
            $validated['client_id'] => [
                'notes' => $validated['notes'] ?? null,
                'source' => $validated['source'] ?? 'manual_add',
            ],
        ]);

        return response()->json(['message' => 'Client added to your book'], 201);
    }

    public function updateNotes(Request $request, User $client)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403);

        $validated = $request->validate(['notes' => 'nullable|string|max:1000']);

        $profile->clients()->updateExistingPivot($client->id, ['notes' => $validated['notes']]);

        return response()->json(['message' => 'Notes updated']);
    }

    public function destroy(Request $request, User $client)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403);

        $profile->clients()->detach($client->id);

        return response()->json(['message' => 'Client removed from your book']);
    }
}

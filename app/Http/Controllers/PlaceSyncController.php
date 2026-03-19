<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Services\Places\PlaceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlaceSyncController extends Controller
{
    public function store(Place $place, PlaceSyncService $service, Request $request): RedirectResponse
    {
        if ((int) $place->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $result = $service->sync($place, $request);

        if (($result['status'] ?? 'failed') === 'success') {
            return back()->with('success', 'Sync completed successfully.');
        }

        return back()->with('error', $result['error_message'] ?? 'Sync failed.');
    }
}

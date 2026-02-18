<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $settings = Setting::where('user_id', $userId)
            ->whereIn('key', Setting::allowedKeys())
            ->get()
            ->keyBy('key');

        $result = [];
        foreach (Setting::allowedKeys() as $key) {
            $result[] = [
                'key' => $key,
                'value' => $settings[$key]->value ?? '',
            ];
        }

        return Inertia::render('Settings', [
            'settings' => $result,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|in:' . implode(',', Setting::allowedKeys()),
            'settings.*.value' => 'required|string|max:255',
        ]);

        $userId = auth()->id();

        foreach ($data['settings'] as $setting) {
            Setting::updateOrCreate(
                ['user_id' => $userId, 'key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        return back()->with('success', true);
    }

}

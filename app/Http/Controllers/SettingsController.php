<?php
namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $allowedKeys = Setting::allowedKeys();

        $settings = Setting::where('user_id', $userId)
            ->whereIn('key', $allowedKeys)
            ->get()
            ->keyBy('key');

        return Inertia::render('Settings/Index', [
            'settings' => $this->formatSettingsForView($allowedKeys, $settings),
        ]);
    }

    private function formatSettingsForView(array $allowedKeys, $settings): array
    {
        $result = [];

        foreach ($allowedKeys as $key) {
            $result[] = [
                'key' => $key,
                'value' => $settings[$key]->value ?? '',
            ];
        }

        return $result;
    }

    public function update(UpdateSettingsRequest $request)
    {
        $data = $request->validated();

        $userId = $request->user()->id;

        foreach ($data['settings'] ?? [] as $setting) {
            if (!in_array($setting['key'], Setting::allowedKeys(), true)) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['user_id' => $userId, 'key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        $request->session()->forget('yandex');

        return back()->with('success', true);
    }

}

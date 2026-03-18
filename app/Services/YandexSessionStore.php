<?php

namespace App\Services;

use Illuminate\Http\Request;

class YandexSessionStore
{
    public function get(Request $request): array
    {
        $session = $request->session();

        return [
            'businessId' => $session->get('yandex.businessId', ''),
            'csrfToken' => $session->get('yandex.csrfToken', ''),
            'sessionId' => $session->get('yandex.sessionId', ''),
            'reqId' => $session->get('yandex.reqId', ''),
            'rating' => $session->get('yandex.rating', ''),
            'name' => $session->get('yandex.name', ''),
            'reviewCount' => $session->get('yandex.reviewCount', ''),
            'cookies' => $session->get('yandex.cookies', []),
        ];
    }

    public function put(Request $request, array $data): void
    {
        $request->session()->put([
            'yandex.businessId' => $data['businessId'] ?? '',
            'yandex.csrfToken' => $data['csrfToken'] ?? '',
            'yandex.sessionId' => $data['sessionId'] ?? '',
            'yandex.reqId' => $data['reqId'] ?? '',
            'yandex.name' => $data['name'] ?? '',
            'yandex.rating' => $data['rating'] ?? '',
            'yandex.reviewCount' => $data['reviewCount'] ?? '',
            'yandex.cookies' => $data['cookies'] ?? [],
        ]);
    }

    public function putPartial(Request $request, array $data): void
    {
        $session = $request->session();

        if (array_key_exists('cookies', $data)) {
            $session->put('yandex.cookies', $data['cookies']);
        }

        if (array_key_exists('csrfToken', $data)) {
            $session->put('yandex.csrfToken', $data['csrfToken']);
        }
    }

    public function hasRequiredData(array $data): bool
    {
        return !empty($data['businessId'])
            && !empty($data['csrfToken'])
            && !empty($data['sessionId'])
            && !empty($data['reqId']);
    }
}

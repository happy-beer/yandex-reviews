<?php

namespace Tests\Unit;

use App\Services\YandexSessionStore;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\TestCase;

class YandexSessionStoreTest extends TestCase
{
    private function makeRequestWithSession(array $sessionData = []): Request
    {
        $session = new Store('test', app('session')->driver()->getHandler());
        $session->start();

        foreach ($sessionData as $key => $value) {
            $session->put($key, $value);
        }

        $request = Request::create('/');
        $request->setLaravelSession($session);

        return $request;
    }

    public function test_get_returns_yandex_session_data(): void
    {
        $request = $this->makeRequestWithSession([
            'yandex.businessId' => '123',
            'yandex.csrfToken' => 'token',
            'yandex.sessionId' => 'session',
            'yandex.reqId' => 'req',
            'yandex.rating' => '4.9',
            'yandex.name' => 'Test Org',
            'yandex.reviewCount' => '120',
            'yandex.cookies' => ['cookie1'],
        ]);

        $store = new YandexSessionStore();

        $result = $store->get($request);

        $this->assertSame([
            'businessId' => '123',
            'csrfToken' => 'token',
            'sessionId' => 'session',
            'reqId' => 'req',
            'rating' => '4.9',
            'name' => 'Test Org',
            'reviewCount' => '120',
            'cookies' => ['cookie1'],
        ], $result);
    }

    public function test_put_writes_full_yandex_session_data(): void
    {
        $request = $this->makeRequestWithSession();

        $data = [
            'businessId' => '123',
            'csrfToken' => 'token',
            'sessionId' => 'session',
            'reqId' => 'req',
            'name' => 'Test Org',
            'rating' => '4.9',
            'reviewCount' => '120',
            'cookies' => ['cookie1'],
        ];

        $store = new YandexSessionStore();
        $store->put($request, $data);

        $this->assertSame('123', $request->session()->get('yandex.businessId'));
        $this->assertSame('token', $request->session()->get('yandex.csrfToken'));
        $this->assertSame('session', $request->session()->get('yandex.sessionId'));
        $this->assertSame('req', $request->session()->get('yandex.reqId'));
        $this->assertSame('Test Org', $request->session()->get('yandex.name'));
        $this->assertSame('4.9', $request->session()->get('yandex.rating'));
        $this->assertSame('120', $request->session()->get('yandex.reviewCount'));
        $this->assertSame(['cookie1'], $request->session()->get('yandex.cookies'));
    }

    public function test_put_partial_updates_only_passed_keys(): void
    {
        $request = $this->makeRequestWithSession([
            'yandex.businessId' => '123',
            'yandex.csrfToken' => 'old-token',
            'yandex.cookies' => ['old-cookie'],
        ]);

        $store = new YandexSessionStore();

        $store->putPartial($request, [
            'csrfToken' => 'new-token',
            'cookies' => ['new-cookie'],
        ]);

        $this->assertSame('123', $request->session()->get('yandex.businessId'));
        $this->assertSame('new-token', $request->session()->get('yandex.csrfToken'));
        $this->assertSame(['new-cookie'], $request->session()->get('yandex.cookies'));
    }

    public function test_has_required_data_returns_true_when_all_required_fields_exist(): void
    {
        $store = new YandexSessionStore();

        $data = [
            'businessId' => '123',
            'csrfToken' => 'token',
            'sessionId' => 'session',
            'reqId' => 'req',
        ];

        $this->assertTrue($store->hasRequiredData($data));
    }

    public function test_has_required_data_returns_false_when_some_required_fields_are_missing(): void
    {
        $store = new YandexSessionStore();

        $data = [
            'businessId' => '123',
            'csrfToken' => '',
            'sessionId' => 'session',
            'reqId' => 'req',
        ];

        $this->assertFalse($store->hasRequiredData($data));
    }
}

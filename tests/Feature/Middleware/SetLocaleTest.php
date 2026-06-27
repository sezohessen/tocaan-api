<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

function runSetLocale(?string $query, ?string $sessionLocale = null): string
{
    $request = Request::create('/admin', 'GET', $query !== null ? ['locale' => $query] : []);
    $session = new Store('test', new ArraySessionHandler(60));
    if ($sessionLocale !== null) {
        $session->put('locale', $sessionLocale);
    }
    $request->setLaravelSession($session);

    app()->setLocale('en');

    (new SetLocale)->handle($request, fn ($r) => response('ok'));

    return app()->getLocale();
}

it('sets a supported locale from the query string', function () {
    expect(runSetLocale('ar'))->toBe('ar');
});

it('falls back to the app default for an unsupported locale', function () {
    config()->set('app.locale', 'en');

    expect(runSetLocale('fr'))->toBe('en');
});

it('uses the session locale when no query is present', function () {
    expect(runSetLocale(null, 'ar'))->toBe('ar');
});

it('persists the resolved locale to the session', function () {
    $request = Request::create('/admin', 'GET', ['locale' => 'ar']);
    $session = new Store('test', new ArraySessionHandler(60));
    $request->setLaravelSession($session);

    (new SetLocale)->handle($request, fn ($r) => response('ok'));

    expect($session->get('locale'))->toBe('ar');
});

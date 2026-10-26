---
title: Laravel
order: 16
---

# Laravel

omnibridge/laravel has no Omnifund provider: a service provider of the application builds the
registry once, from a config file, with a `symfony/http-client` client (the providers take a
`Symfony\Contracts\HttpClient\HttpClientInterface`; Laravel's own HTTP client is Guzzle's, which
they do not take).

```php
// config/omnifund.php
return [
    'providers' => [
        'github' => ['factory' => 'github-sponsors', 'options' => [
            'account' => 'glitchr-studio',
            'token' => env('GITHUB_SPONSORS_TOKEN'),
        ]],
        'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
    ],
];
```

```php
// app/Providers/OmnifundServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Omnifund\GitHubSponsors\GitHubSponsorsFactory;
use Omnifund\Liberapay\LiberapayFactory;
use Omnifund\Registry;
use Symfony\Component\HttpClient\HttpClient;

final class OmnifundServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, function (): Registry {
            $http = HttpClient::create(['timeout' => 10]);

            return new Registry([new GitHubSponsorsFactory($http), new LiberapayFactory($http)], config('omnifund.providers'));
        });
    }
}
```

Register the provider (`bootstrap/providers.php` on Laravel 11 and later), then:

```php
use Illuminate\Support\Facades\Cache;
use Omnifund\Registry;

$sponsors = Cache::remember('sponsors.github', 3600, fn () => array_map(
    static fn ($s) => $s->toArray(),
    app(Registry::class)->sponsors('github')->sponsors(),
));
```

Keep arrays in the cache (`toArray()`): the models are plain readonly objects, but arrays survive
any cache store. Configuration caching (`php artisan config:cache`) keeps working: the options are
plain arrays read through `config()`. This page was written from the registry's API; it was not
run in a Laravel application.

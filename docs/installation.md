---
title: Installation
order: 10
---

# Installation

```sh
composer require omnisense/omnifund omnifund/liberapay symfony/http-client
```

Take the providers the project is funded on: `omnifund/github-sponsors`,
`omnifund/opencollective`, `omnifund/liberapay`.

## Requirements

- PHP 8.2 or later.
- `omnisense/omnifund` requires `symfony/http-client-contracts` (^3.0), interfaces only, and
  nothing else.
- Each provider requires `omnisense/omnifund` (1.x) and `symfony/http-client-contracts` (^3.0):
  no HTTP client of its own. The application gives one - `symfony/http-client`, or any
  implementation of the contracts - to each factory's constructor; there is no default.
- `ext-json` (always present since PHP 8.0).

## Plain PHP

The block below runs as written in the tests (`Tests/DocsTest.php`, which checks the three
printed lines against the comments), with a recorded answer in place of Liberapay's.

```php
<?php

require 'vendor/autoload.php';

use Omnifund\Liberapay\LiberapayFactory;
use Omnifund\Registry;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

// In production: Symfony\Component\HttpClient\HttpClient::create(['timeout' => 10]).
$http = new MockHttpClient(new MockResponse('{"username":"Liberapay","display_name":null,"npatrons":1782,"receiving":{"amount":"636.28","currency":"EUR"},"goal":{"amount":"1000.00","currency":"EUR"},"summaries":[]}'));

$registry = new Registry([new LiberapayFactory($http)], [
    'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
]);
$profile = $registry->profile('liberapay')->profile();

echo $profile->sponsors, " patrons\n";
// 1782 patrons
echo $profile->income?->monthly()?->decimal(), ' ', $profile->income?->amount->currency, " a month\n";
// 2757.21 EUR a month
echo $profile->goal?->percent, "% of the goal\n";
// 63% of the goal
```

Liberapay counts by the week: `Income::monthly()` turns 636.28 a week into a month of 52/12
weeks. Several providers sit in one registry, each under the name the application gives it:

```php
use Omnifund\GitHubSponsors\GitHubSponsorsFactory;
use Omnifund\Liberapay\LiberapayFactory;
use Omnifund\OpenCollective\OpenCollectiveFactory;
use Omnifund\Registry;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create(['timeout' => 10]);
$registry = new Registry([new GitHubSponsorsFactory($http), new OpenCollectiveFactory($http), new LiberapayFactory($http)], [
    'github' => ['factory' => 'github-sponsors', 'options' => ['account' => 'glitchr-studio', 'token' => $_ENV['GITHUB_TOKEN']]],
    'oc' => ['factory' => 'opencollective', 'options' => ['account' => 'webpack']],
    'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
]);
foreach ($registry->sponsors('github')->sponsors() as $sponsor) { /* $sponsor->label(), ->avatarUrl, ->url */ }
```

Building a provider calls nothing: a provider is built the first time the registry is asked for
it, its required options checked then (`The "github-sponsors" provider needs: token.`), and a
platform is called only by `profile()`, `sponsors()` or `tiers()`.

Cache what you read: sponsors change slowly, and each platform limits its calls (GitHub's
GraphQL points, Open Collective's 10 anonymous calls a minute). omnifund keeps nothing itself
([Usage](usage.md#caching)).

No class of a framework is loaded on the way: `Tests/BareTest.php` runs the harness's bare
script in a process of its own and checks that nothing but the package and the HTTP client
contracts was loaded ([harness](harness.md)).

## In a framework

There is no Omnifund bundle and no provider in omnibridge/symfony or omnibridge/laravel yet:
build the `Registry` once where the framework builds its services - [Symfony](symfony.md),
[Laravel](laravel.md).

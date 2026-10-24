# Installation, in plain PHP

    composer require omniscient/omnifund omnifund/liberapay symfony/http-client

Any `symfony/http-client-contracts` client does: the factories take the application's own. The
block below runs as written in the tests (`Tests/DocsTest.php`), with a recorded answer in place
of Liberapay's.

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

```
$registry = new Registry([new GitHubSponsorsFactory($http), new OpenCollectiveFactory($http), new LiberapayFactory($http)], [
    'github' => ['factory' => 'github-sponsors', 'options' => ['account' => 'glitchr-studio', 'token' => $_ENV['GITHUB_TOKEN']]],
    'oc' => ['factory' => 'opencollective', 'options' => ['account' => 'webpack']],
    'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
]);
foreach ($registry->sponsors('github')->sponsors() as $sponsor) { /* $sponsor->label(), ->avatarUrl, ->url */ }
```

Cache what you read: sponsors change slowly, and each platform limits its calls (GitHub's
GraphQL points, Open Collective's 10 anonymous calls a minute). omnifund keeps nothing itself.

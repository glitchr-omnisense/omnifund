# omnisense/omnifund

Who funds a project, read under any PHP host, wherever the funding is received: the account's
profile (how many fund it, what it receives, its goal), its public sponsors and the tiers it
offers - from GitHub Sponsors, Open Collective or Liberapay, through one set of models. Plain
PHP: no framework in `require`, the HTTP client is the application's.

```php
use Omnifund\GitHubSponsors\GitHubSponsorsFactory;
use Omnifund\Liberapay\LiberapayFactory;
use Omnifund\Registry;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create(['timeout' => 10]);
$registry = new Registry([new GitHubSponsorsFactory($http), new LiberapayFactory($http)], [
    'github' => ['factory' => 'github-sponsors', 'options' => ['account' => 'glitchr-studio', 'token' => $token]],
    'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
]);

$registry->profile('github')->profile()->goal;          // Model\Goal: "$500 a month", 12%
$registry->sponsors('github')->sponsors();              // list<Model\Sponsor>, public ones
$registry->tiers('github')->tiers();                    // list<Model\Tier>
$registry->profile('liberapay')->profile()->income;     // Model\Income: 636.28 EUR a week
```

## The shape

- **Models** (`Omnifund\Model`): `Profile` (handle, name, page, picture, sponsors counted,
  `Income`, `Goal`), `Sponsor` (handle, name, picture, page, kind, since, tier, total given),
  `Tier` (name, price, period, custom amount, minimum), `Money` (hundredths of an ISO 4217
  currency), `Period` (once, week, month, year), `Income` (an amount by period, `monthly()`).
- **Contracts by capability**, each provider saying which it has (`Capabilities`, with whether a
  key is needed and the terms that apply): `ProfileInterface`, `SponsorsInterface`,
  `TiersInterface`. Each method reads the configured account, or another one given.
  `Registry`, `Config`, `ProviderFactory`: providers by name, as in the other omni families.
- **Http\Json**: a JSON call on the application's client, its failures as omnifund's exceptions
  (`UnreachableException`, `ProviderException`), and typed reads of an answer.
- **Providers** are packages:
  [omnifund/github-sponsors](https://github.com/glitchr-omnisense/omnifund-github-sponsors) (profile,
  sponsors, tiers; a token),
  [omnifund/opencollective](https://github.com/glitchr-omnisense/omnifund-opencollective) (profile,
  backers, tiers; no key),
  [omnifund/liberapay](https://github.com/glitchr-omnisense/omnifund-liberapay) (profile only:
  Liberapay keeps patrons private and has no tiers; no key).
- **Hosts**: nothing of a framework here. The bridges (omnibridge/symfony, omnibridge/laravel)
  wire omnifund when the first site needs it; until then a site builds the `Registry` itself.
- **Testing\ArrayProvider**: a provider answering fixed data, for tests and demos.
- No cache inside: sponsors change slowly and every platform limits its calls - keep what you
  read in the application's cache.

## Documentation

- [Installation, in plain PHP](docs/installation.md) (run as written by the tests)
- [Writing a provider](docs/providers.md)
- [The harness](docs/harness.md)

## Verified, and not

- Verified (`docker compose run --rm omnifund test` in `docker/`, PHP 8.4, 2026-10-24): the
  models (amounts from decimals and back, an income by the month, a week as 52/12 of a month);
  the registry (providers built once, a capability refused, a provider or an option missing);
  the typed readers; each provider on recorded answers, every request checked (endpoint, method,
  headers, query and variables, paging, limits, errors: unknown account, no listing, bad token,
  rate limit); in plain PHP, a registry read loading nothing but omnifund and Symfony's HTTP
  client contracts (`BareTest`); the installation page run as written (`DocsTest`). PHPStan at
  the highest level, no baseline, each package.
- Verified live (`docker compose run --rm omnifund live`, 2026-10-24): Open Collective's
  GraphQL API without a key (webpack: profile, tiers, three backers); Liberapay's public.json
  (the Liberapay team); GitHub's GraphQL API with a token (glitchr-studio's listing,
  sindresorhus's tiers and two sponsors).
- Not verified: GitHub's goal as a live answer (no listing at hand had an active goal: the
  shape is GitHub's schema, accepted by the live query); an Open Collective personal token;
  any site using omnifund (none yet - the omnisense site still reads GitHub Sponsors with its
  own call).

## Responsibility

omnisense/omnifund and its omnifund/* providers are unofficial clients, affiliated with none of
the services they reach. The MIT licence covers the code only and grants no right to any service
or its data; whoever runs them complies with each service's terms (named in each provider's
README and in `Capabilities::$terms`) and obtains what they require. Sponsors' names and pictures
are personal data: show only what the platform shows publicly, and follow its rules.

## Licence

MIT.

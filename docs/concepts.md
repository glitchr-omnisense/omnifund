---
title: Concepts
order: 20
---

# Concepts

## Provider and its contracts

`Omnifund\ProviderInterface` is one funding platform, configured for an account:

| Method | Returns |
|---|---|
| `getName(): string` | the factory's name: `github-sponsors`, `opencollective`, `liberapay`, `array` |
| `getTitle(): string` | the platform's display name: `GitHub Sponsors`, `Open Collective`, `Liberapay`, `Fixed data` |
| `capabilities(): Capabilities` | what it can be asked, whether it needs a key, whose terms apply; calls nothing |

A provider implements, beside it, the contracts it can answer. Each extends `ProviderInterface`,
and each method reads the configured account, or another one given as `$account`:

| Interface | Method | Returns |
|---|---|---|
| `ProfileInterface` | `profile(?string $account = null): Profile` | the account as it shows itself |
| `SponsorsInterface` | `sponsors(?string $account = null, ?int $limit = null): list<Sponsor>` | who funds it, as far as the platform shows them publicly; `$limit` null: every one, page after page |
| `TiersInterface` | `tiers(?string $account = null): list<Tier>` | the ways to give it offers |

## Capabilities

`Omnifund\Capabilities` (readonly):

| Property | Type | Default | |
|---|---|---|---|
| `profile` | bool | `false` | implements `ProfileInterface` |
| `sponsors` | bool | `false` | implements `SponsorsInterface` |
| `tiers` | bool | `false` | implements `TiersInterface` |
| `key` | bool | `false` | a key or token is needed to read it |
| `terms` | `list<string>` | `[]` | the URLs of the terms whoever runs it must follow |

`contracts()` lists the contracts it has (`['profile', 'sponsors', 'tiers']`), `toArray()` all of
it. What each provider answers:

| | `profile` | `sponsors` | `tiers` | `key` | `terms` |
|---|---|---|---|---|---|
| GitHub Sponsors | yes | yes | yes | yes | GitHub's Terms of Service, the GitHub Sponsors additional terms, the GraphQL rate limits |
| Open Collective | yes | yes | yes | no | opencollective.com/tos, the API v2 documentation |
| Liberapay | yes | no | no | no | liberapay.com/about/legal, liberapay.com/about/privacy |
| `array` | yes | yes | yes | no | - |

## Factory and Config

`Omnifund\ProviderFactoryInterface` builds a provider from options: `getName(): string` and
`create(array $options = []): ProviderInterface`. Every provider extends
`Omnifund\ProviderFactory`, which does the work:

1. `createConfig($options)`: the options in a `Config` (an `ArrayObject`), then the factory's
   `populate()` sets its name (`omnifund.factory_name`), its title (`omnifund.factory_title`), its
   required options (`omnifund.required_options`) and the defaults of the others
   (`Config::defaults()` sets only keys not set yet);
2. the required options are checked: one missing, `''` or `[]` is an `InvalidConfigException`
   listing every missing key (`The "github-sponsors" provider needs: token.`);
3. `build($config)` constructs the provider.

Nothing is called on the way. A factory whose provider is reached over HTTP takes the client in
its own constructor - the three providers require one: there is no default client.

`Config`'s readers, for a factory's `build()`:

| Method | |
|---|---|
| `get(string $key, mixed $default = null)` | the raw value |
| `string(string $key): ?string` | a scalar as a string; `null` when it is empty |
| `int(string $key, int $default = 0): int`, `float(string $key, float $default = 0.0): float` | a numeric value, else the default |
| `bool(string $key): bool` | a bool, or `"1"`, `"true"`, `"yes"`, `"on"` (any case) as an environment variable gives it |
| `validateNotEmpty(list<string> $keys): void` | step 2 |

## Registry

`Omnifund\Registry` holds the application's providers by name:

```php
$registry = new Registry(
    [new GitHubSponsorsFactory($http), new LiberapayFactory($http)],     // iterable of factories, keyed by getName()
    [
        'github' => ['factory' => 'github-sponsors', 'options' => ['account' => 'glitchr-studio', 'token' => $token]],
        'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
    ],
);
```

| Method | Returns |
|---|---|
| `get(string $name): ProviderInterface` | the provider, built the first time and kept |
| `profile(string $name): ProfileInterface` | the provider as a profile; `NotSupportedException` when it is not one |
| `sponsors(string $name): SponsorsInterface` | likewise, as sponsors |
| `tiers(string $name): TiersInterface` | likewise, as tiers |
| `has(string $name): bool` | whether the name is configured |
| `options(string $name): array` | its configured options |
| `names(): list<string>` | the configured names |
| `factories(): list<string>` | the factories installed: what `factory` may name |

A name not configured is an `InvalidConfigException` (`No "x" funding provider; configured:
github, liberapay.`), and so is a factory not given (`No "missing" factory for the "nowhere"
funding provider; installed: array.`). There is no `create()` with overrides: a provider with
other options is built with its factory, `(new GitHubSponsorsFactory($http))->create([...])`.

## Money

`Omnifund\Model\Money` (readonly): `int $cents`, hundredths of the currency's unit, as the
platforms count them (GitHub's `monthlyPriceInCents`, Open Collective's `valueInCents`,
Liberapay's `"636.28"`), and `string $currency`, ISO 4217, upper-cased.

| Method | |
|---|---|
| `__construct(int $cents, string $currency)` | `InvalidConfigException` when the currency is not three letters |
| `fromDecimal(string\|int\|float $amount, string $currency): self` | from `"636.28"`, `636.28`, `5`; `InvalidConfigException` when it is not numeric |
| `decimal(): string` | `"636.28"`, with a `-` for a negative amount |
| `multiply(float $factor): self` | rounded to the cent |
| `toArray()` | `{cents, currency, decimal}` |

Every currency is counted in hundredths, whatever its minor unit: the providers' own amounts are.

## Period

`Omnifund\Model\Period` (string enum): `Once` (`once`), `Week` (`week`), `Month` (`month`),
`Year` (`year`). `perMonth(): ?float` is how many of this period make a month: 52/12 for a week
(52 weeks a year), 1 for a month, 1/12 for a year, `null` for once.

## Profile

`Omnifund\Model\Profile` (readonly), the account as it shows itself; each field `null` when the
provider does not say:

| Property | Type | |
|---|---|---|
| `provider` | string | the factory's name |
| `handle` | string | the account's login or slug, as the platform writes it |
| `name` | `?string` | its display name |
| `description` | `?string` | its short description, or summary |
| `url` | `?string` | its funding page |
| `avatarUrl` | `?string` | its picture |
| `sponsors` | `?int` | how many fund it (private ones included where the platform counts them) |
| `income` | `?Income` | what it receives, by period |
| `goal` | `?Goal` | what it aims for |

`toArray()` gives the same, nested models as arrays.

## Income and Goal

`Income` (readonly): `Money $amount` and `Period $period`. `monthly(): ?Money` is the same
income by the month (`null` for a one-time amount): 636.28 a week is 2757.21 a month.

`Goal` (readonly), what an account aims for - an amount, or a number of sponsors:

| Property | Type | |
|---|---|---|
| `title` | `?string` | GitHub's goal title |
| `description` | `?string` | |
| `amount` | `?Money` | an amount goal (Liberapay, GitHub's `MONTHLY_SPONSORSHIP_AMOUNT`) |
| `period` | `?Period` | the amount's period |
| `sponsors` | `?int` | a number of sponsors (GitHub's `TOTAL_SPONSORS_COUNT`) |
| `percent` | `?int` | how far it has got, when the provider says (or computes) |

## Sponsor

`Omnifund\Model\Sponsor` (readonly), someone who funds the account, as the platform shows them
publicly:

| Property | Type | Default | |
|---|---|---|---|
| `handle` | string | - | their login or slug |
| `name` | `?string` | `null` | |
| `avatarUrl` | `?string` | `null` | |
| `url` | `?string` | `null` | their page |
| `kind` | string | `Sponsor::USER` | `user`, `organization` or `other` (the constants `USER`, `ORGANIZATION`, `OTHER`) |
| `since` | `?\DateTimeImmutable` | `null` | since when (Open Collective) |
| `tier` | `?string` | `null` | their tier's name (Open Collective) |
| `total` | `?Money` | `null` | what they gave in all (Open Collective) |

`label()` is the name, else the handle. Sponsors' names and pictures are personal data: show only
what the platform shows publicly.

## Tier

`Omnifund\Model\Tier` (readonly), one way to give:

| Property | Type | Default | |
|---|---|---|---|
| `name` | string | - | "$5 a month", "Backer" |
| `price` | `?Money` | `null` | `null` for an amount left to the giver |
| `period` | `Period` | `Month` | |
| `id` | `?string` | `null` | the platform's id |
| `description` | `?string` | `null` | |
| `customAmount` | bool | `false` | the giver chooses the amount |
| `minimum` | `?Money` | `null` | the least accepted (Open Collective) |

## Http\Json

`Omnifund\Http\Json` is the providers' HTTP helper, public for an application's own provider:

- `request(HttpClientInterface $http, string $method, string $url, array $options, string
  $provider, string $what): array` - the call, the HTTP client's failures as an
  `UnreachableException`, a status of 300 or more as a `ProviderException`
  (`GitHub answered 401 for the listing of x.`), a body that is not a JSON object or list as a
  `ProviderException` (`... answered something that is not JSON for ...`);
- typed reads of an answer, each `null` (or `[]`, `false`) when a step of the path is missing:
  `get($data, ...$path)`, `string()`, `int()`, `bool()` (only `true` is true), `list()`,
  `date()`.

```php
$answer = json_decode('{"data":{"account":{"stats":{"contributorsCount":"42"},"since":"2016-10-15T15:47:40.951Z"}}}', true);
Json::int($answer, 'data', 'account', 'stats', 'contributorsCount');   // 42
Json::date($answer, 'data', 'account', 'since')?->format('Y-m-d');      // "2016-10-15"
Json::string($answer, 'data', 'nothing');                               // null
```

(the harness's bare script, run on 2026-10-26)

# omnisense/omnifund

Omnifund reads who funds a project, wherever the funding is received: the account's **profile**
(its name and page, how many fund it, what it receives, its goal), its public **sponsors** and the
**tiers** it offers - from GitHub Sponsors, Open Collective or Liberapay, through one set of
models. A site shows a "Sponsors" band, a goal bar or a "Support us" page from any of them
without knowing whose API answered.

```php
$registry->profile('github')->profile()->goal;          // Model\Goal: "$500 a month", 12 %
$registry->sponsors('github')->sponsors();              // list<Model\Sponsor>, the public ones
$registry->tiers('oc')->tiers();                        // list<Model\Tier>
$registry->profile('liberapay')->profile()->income;     // Model\Income: 636.28 EUR a week
```

Each provider implements the **contracts** it can answer - `ProfileInterface`,
`SponsorsInterface`, `TiersInterface` - and says so, with whether it needs a key and whose terms
apply, in its `Capabilities`. The design is the Omnisense families' one: a provider's
**factory** builds it from options, a **registry** holds the application's providers by name.
The package is plain PHP: it requires `symfony/http-client-contracts` (interfaces only) and
nothing else, and the HTTP client is the application's.

Published as `omnisense/omnifund`, formerly `omniscient/omnifund`: the package `replace`s the old
name, so a `composer.json` that still requires it resolves to this package. The namespace is
`Omnifund\`; providers keep the `omnifund/` vendor and the `Omnifund\<Provider>\` namespace.

## When to use it

- A site or a README badge that shows its sponsors, its backers or its funding goal, read from
  the platform that receives the money.
- A project funded on more than one platform that wants one list and one total.
- A "Support us" page that lists the tiers each platform offers.

It does not take payments, does not write anything to the platforms, and keeps nothing: no
cache inside. Sponsors change slowly and every platform limits its calls: keep what is read in
the application's cache.

## Providers

| Package | Factory name | Profile | Sponsors | Tiers | Key | Income counted by |
|---|---|---|---|---|---|---|
| [`omnifund/github-sponsors`](https://github.com/glitchr-omnisense/omnifund-github-sponsors) | `github-sponsors` | yes (no income) | the public ones | yes | a GitHub token | - |
| [`omnifund/opencollective`](https://github.com/glitchr-omnisense/omnifund-opencollective) | `opencollective` | yes (no goal) | the backers | yes | none (a token raises the limit) | the year (the yearly budget) |
| [`omnifund/liberapay`](https://github.com/glitchr-omnisense/omnifund-liberapay) | `liberapay` | yes | - | - | none | the week |
| `Omnifund\Testing\ArrayFactory` (in the core) | `array` | yes | yes | yes | - | as given |

The contracts are what each provider class implements and its `capabilities()` says (probed in
the [harness](harness.md) on 2026-10-26). Liberapay shows no patron by name and has no tiers:
the registry refuses `sponsors('liberapay')` and `tiers('liberapay')` with a
`NotSupportedException`.

## How this manual is organised

- [Installation](installation.md): requirements, a first script in plain PHP (run as written by
  the tests).
- [Symfony](symfony.md) and [Laravel](laravel.md): no bridge yet; the registry as a service, by
  hand.
- [Concepts](concepts.md): providers and their contracts, capabilities, factories, the registry,
  every model.
- [Usage](usage.md): profiles, sponsors, tiers, several platforms at once, caching.
- [Configuration reference](configuration.md): the registry, every provider's options.
- [Errors](errors.md): the four exceptions and when each is thrown.
- [Testing](testing.md): the array provider, recorded answers.
- [The Docker harness](harness.md).
- [Writing a provider](providers.md).
- [Verified, and not](verified.md).

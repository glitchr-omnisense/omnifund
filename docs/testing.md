---
title: Testing
order: 60
---

# Testing

## The array provider

`Omnifund\Testing\ArrayProvider` answers what it was given, with every contract: for tests,
demos and an application's fixtures. Its factory, `ArrayFactory` (name `array`), takes the
models as options:

```php
use Omnifund\Model\Goal;
use Omnifund\Model\Income;
use Omnifund\Model\Money;
use Omnifund\Model\Period;
use Omnifund\Model\Profile;
use Omnifund\Model\Sponsor;
use Omnifund\Model\Tier;
use Omnifund\Registry;
use Omnifund\Testing\ArrayFactory;

$registry = new Registry([new ArrayFactory()], [
    'github' => ['factory' => 'array', 'options' => [
        'profile' => new Profile('array', 'glitchr-studio', 'Glitch Art', sponsors: 3,
            income: new Income(Money::fromDecimal('12.00', 'EUR'), Period::Week),
            goal: new Goal('A day a week', amount: Money::fromDecimal('100', 'EUR'), period: Period::Week)),
        'sponsors' => [new Sponsor('ada', 'Ada'), new Sponsor('acme', 'ACME', kind: Sponsor::ORGANIZATION, tier: 'Sponsor'), new Sponsor('bob')],
        'tiers' => [new Tier('Backer', Money::fromDecimal(5, 'EUR')), new Tier('Once', null, Period::Once, customAmount: true)],
    ]],
]);

$registry->profile('github')->profile()->income->monthly()->decimal();   // "52.00"
$registry->sponsors('github')->sponsors(limit: 2);                       // Ada, ACME
```

(the harness's bare script does the same, run on 2026-10-26.) The application keeps the name it
uses (`github`); only the factory changes. `getName()` is `array`, `getTitle()` `Fixed data`;
`profile()` answers the same profile whatever account is asked for.

## A provider on recorded answers

To test the application's handling of a platform's answers and failures, give the factory a
`MockHttpClient`:

```php
use Omnifund\Exception\ProviderException;
use Omnifund\OpenCollective\OpenCollectiveFactory;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

$oc = (new OpenCollectiveFactory(new MockHttpClient(new MockResponse('{"message":"Rate limit exceeded"}', ['http_code' => 429]))))
    ->create(['account' => 'webpack']);

try {
    $oc->sponsors();
} catch (ProviderException $e) {
    // Open Collective answered 429 for the backers of webpack.
}
```

The providers' own tests keep the shapes of live answers (the people invented) in
`Tests/Fixtures/`: `listing.json`, `sponsors-1.json`, `sponsors-2.json`, `no-listing.json`,
`not-found.json` for GitHub; `account.json`, `members-1.json`, `members-2.json`, `not-found.json`
for Open Collective; `public.json`, `hidden.json` for Liberapay. An application may start from
them.

Both GraphQL providers send every query as a `POST` with `{"query", "variables"}`: a
`MockHttpClient` callback can tell the listing (or account) query from the sponsors (or
members) one by the query text.

## Live checks

Each provider has a `LiveTest` (PHPUnit group `live`, left out of `test`) that reads a real
account: `docker compose run --rm omnifund live` in the [harness](harness.md). Open Collective
and Liberapay need no key; GitHub runs when `OMNIFUND_GITHUB_TOKEN` is set, and is skipped
otherwise.

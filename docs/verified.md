---
title: Verified, and not
order: 90
---

# Verified, and not

## What the tests prove

Run on 2026-10-26 in the [harness](harness.md), every package from its checkout, PHP 8.4, the
`live` group left out, no network: 39 tests, 204 assertions, green.

- The core: the models (amounts from decimals and back, a currency or an amount refused, an
  income by the month, a week as 52/12 of a month, a sponsor shown by name or handle, the typed
  readers); the registry (providers built once, a capability refused, a provider not configured,
  a required option missing); in plain PHP, a registry read loading nothing but omnifund and
  Symfony's HTTP client contracts, and every class loading with the required packages alone
  (`BareTest`); the installation page's script run as written (`DocsTest`).
- Each provider on recorded answers (the shapes of live answers of 2026-10-24, the people
  invented): every request's endpoint, method, headers, query and variables; paging and limits;
  the profile, the goal (GitHub's amount and count goals), the tiers; an unknown account, an
  account without a listing, a bad token, a rate limit, an account hiding its income, the
  summary's language, the capability refused; the factories' required options.

The examples of this manual (the three providers on their fixtures, the error messages, the
testing page) were run in the harness on 2026-10-26.

## What was run live

The providers' READMEs state live runs on 2026-10-24 (`docker compose run --rm omnifund live`):
Open Collective's GraphQL API without a key (webpack: profile, tiers, three backers), Liberapay's
public.json (the Liberapay team), GitHub's GraphQL API with a token (glitchr-studio's listing,
sindresorhus's tiers and two sponsors). They were not run again for this manual.

## What is not verified

- GitHub's goal as a live answer: no listing at hand had an active goal; the shape is GitHub's
  schema, accepted by the live query.
- An Open Collective personal token.
- Any site using omnifund: none yet.
- The [Symfony](symfony.md) and [Laravel](laravel.md) pages were written from the classes'
  constructors, not run in an application.

## Responsibility

omnisense/omnifund and its omnifund/* providers are unofficial clients, affiliated with none of
the services they reach. The MIT licence covers the code only and grants no right to any service
or its data; whoever runs them complies with each service's terms (named in each provider's
README and in `Capabilities::$terms`) and obtains what they require. Sponsors' names and pictures
are personal data: show only what the platform shows publicly, and follow its rules.

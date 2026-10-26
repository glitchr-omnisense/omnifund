---
title: Configuration reference
order: 40
---

# Configuration reference

## The registry

```php
new Registry(iterable $factories, array $config);
```

| Key | Type | Required | |
|---|---|---|---|
| `<name>` | string | - | the name the application gives the provider: `github`, `oc`... |
| `<name>.factory` | string | yes | a factory's name: `github-sponsors`, `opencollective`, `liberapay`, `array` |
| `<name>.options` | array | no (`[]`) | the provider's options, below |

Two accounts on the same platform are two entries with the same factory - or one entry, and the
other account passed to each call (`profile('another')`).

Options are taken as given: unlike some Omnisense families, an empty string is not removed before
the defaults apply. A required option given as `null`, `''` or `[]` is reported missing; an
optional one given as `''` reads as not set (`Config::string()` gives `null` for it).

## github-sponsors

`Omnifund\GitHubSponsors\GitHubSponsorsFactory(HttpClientInterface $http)`

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `account` | string | - | yes | the user or organization whose listing is read: `glitchr-studio` |
| `token` | string | - | yes | a GitHub token, sent as `Authorization: Bearer`. GitHub's GraphQL API answers no anonymous call; any token reads a public listing and its public sponsors - a fine-grained token with no permission is enough |
| `endpoint` | string | `https://api.github.com/graphql` | no | GitHub Enterprise: `https://HOST/api/graphql` |
| `user_agent` | string | `omnifund (+https://github.com/glitchr-omnisense/omnifund)` | no | sent with every call (GitHub refuses a request without one) |

## opencollective

`Omnifund\OpenCollective\OpenCollectiveFactory(HttpClientInterface $http)`

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `account` | string | - | yes | the collective's slug: `webpack` |
| `token` | string | `''` (none) | no | a personal token, sent as `Personal-Token` when set: it raises the rate limit (10 calls a minute without one) |
| `endpoint` | string | `https://api.opencollective.com/graphql/v2` | no | |
| `user_agent` | string | as above | no | |

## liberapay

`Omnifund\Liberapay\LiberapayFactory(HttpClientInterface $http)`

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `account` | string | - | yes | the username: `Liberapay` |
| `language` | string | `en` | no | the summary's language, when the account wrote several; else the first one |
| `base_url` | string | `https://liberapay.com` | no | the profile is `<base_url>/<account>/public.json` |
| `user_agent` | string | as above | no | Liberapay asks for one naming the application |

## array

`Omnifund\Testing\ArrayFactory()`, no HTTP client ([Testing](testing.md)).

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `profile` | `Model\Profile` | - | yes | answered by `profile()`, whatever the account asked for |
| `sponsors` | `list<Model\Sponsor>` | `[]` | no | answered by `sponsors()`, cut to the limit; entries that are not a `Sponsor` are dropped |
| `tiers` | `list<Model\Tier>` | `[]` | no | answered by `tiers()` |

A `profile` that is not a `Profile`, or `sponsors` / `tiers` that are not arrays, is an
`InvalidConfigException` (`The "array" provider needs a Profile, and lists of sponsors and
tiers.`).

## The HTTP client

The client given to a factory is used as it is: its timeout, proxy and retries are the
application's. `HttpClient::create(['timeout' => 10])` is a reasonable start; Symfony's
`RetryableHttpClient` may wrap it for Open Collective's 429s.

---
title: Errors
order: 50
---

# Errors

Every exception Omnifund throws implements `Omnifund\Exception\OmnifundException` (a
`\Throwable`):

| Exception | Extends | When |
|---|---|---|
| `InvalidConfigException` | `\InvalidArgumentException` | a provider not configured, a factory not installed, a required option missing; a `Money` with a currency that is not three letters, or an amount that is not numeric |
| `NotSupportedException` | `\LogicException` | the registry's `profile()`, `sponsors()` or `tiers()` asked of a provider without that contract: `The "liberapay" funding provider (Omnifund\Liberapay\LiberapayProvider) does not list sponsors.` Thrown before any call |
| `ProviderException` | `\RuntimeException` | the platform answered, and not with what was asked: a status of 300 or more, a body that is not JSON, a GraphQL `errors` member, no such account, no listing |
| `UnreachableException` | `\RuntimeException` | the platform could not be reached: a network error, a timeout; a copy of the HTTP client's exception is the previous one ([no secret in it](#no-secret-in-a-failure)) |

Messages, as the providers write them (run on recorded answers on 2026-10-26):

```
The "github-sponsors" provider needs: token.
GitHub answered 401 for the listing of x.
GitHub answered Could not resolve to a RepositoryOwner with the login of 'nobody-here-0'. for the listing of x.
GitHub answered no GitHub Sponsors listing for the listing of x.
GitHub answered something that is not JSON for the listing of x.
Liberapay could not be reached for the account x: Connection timed out
```

Open Collective writes `Open Collective answered 429 for the backers of webpack.`, `... answered no
such collective for the collective x.`; Liberapay `Liberapay answered 404 for the account x.`.
A status is not told apart further: a 401 (a bad GitHub token) and a 429 (a rate limit) are both
a `ProviderException`, the status in its message only.

## No secret in a failure

An `UnreachableException` keeps the request's secrets out. The HTTP client's message quotes the URL
called: its key parameters, a password in its user part and every secret of the request's options
(GitHub's and Open Collective's tokens) are masked (`api_key=***`, `Http\Redaction::text()`). The
previous exception is a copy of the client's (`Redaction::copy()`: the same class and code, that
message), and no trace of the chain keeps its arguments, where the query and headers stood: a log
that writes the whole chain, arguments included (`zend.exception_ignore_args` off), holds no key.

## What to catch where

| Where | Catch |
|---|---|
| a page that shows sponsors | `UnreachableException` and `ProviderException`: show the last cached list, or nothing; log it |
| a scheduled refresh of the cache | the same; on a `ProviderException` naming 401, check the token |
| building the registry, a deploy | `InvalidConfigException`: a configuration error, to fix |
| a list over every provider | check the contract first (`instanceof`); `NotSupportedException` is a programming error |

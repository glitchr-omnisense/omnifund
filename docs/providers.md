---
title: Writing a provider
order: 80
---

# Writing a provider

A provider is a package `omnifund/<name>` (namespace `Omnifund\<Name>\`) with a factory and a
provider class, requiring `omnisense/omnifund` and `symfony/http-client-contracts`:

- the factory extends `Omnifund\ProviderFactory`: `populate()` names it
  (`omnifund.factory_name`, `omnifund.factory_title`), lists its required options
  (`omnifund.required_options`) and the defaults of the others; `build()` returns the provider.
  The HTTP client comes through the factory's constructor
  (`Symfony\Contracts\HttpClient\HttpClientInterface`), never created inside.
- the provider implements the contracts it can answer - `ProfileInterface`, `SponsorsInterface`,
  `TiersInterface` - and says so in `capabilities()`, with whether a key is needed and the terms
  that apply. A platform that keeps its sponsors private (Liberapay) implements the profile only;
  the registry then refuses `sponsors()` with a `NotSupportedException`.
- every call goes through `Http\Json::request()`, which turns a network failure into an
  `UnreachableException` and an error status into a `ProviderException`; a GraphQL `errors`
  member is a `ProviderException` too. `Http\Json`'s typed readers walk an answer without
  trusting its shape.
- amounts are `Model\Money` in hundredths of the currency, with the `Model\Period` the platform
  counts in (Liberapay: the week; GitHub: the month; Open Collective's budget: the year).
- tests run in the core's harness (`docker/harness/plugins.php` lists the providers), on
  recorded answers (`MockHttpClient`), with a `BareTest` loading every class with the package's
  requirements alone, and a `LiveTest` (group `live`) where the platform can be read without an
  account.

## A skeleton

```php
namespace Omnifund\Kofi;

use Omnifund\Capabilities;
use Omnifund\Config;
use Omnifund\Http\Json;
use Omnifund\Model\Profile;
use Omnifund\ProfileInterface;
use Omnifund\ProviderFactory;
use Omnifund\ProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class KofiFactory extends ProviderFactory
{
    public function __construct(private readonly HttpClientInterface $http)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults([
            'omnifund.factory_name' => 'kofi',
            'omnifund.factory_title' => 'Ko-fi',
            'omnifund.required_options' => ['account'],
            'user_agent' => 'my-site (+https://example.org)',
        ]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new KofiProvider($this->http, (string) $c->string('account'), (string) $c->string('user_agent'));
    }
}

final class KofiProvider implements ProfileInterface
{
    public function __construct(private readonly HttpClientInterface $http, private readonly string $account, private readonly string $userAgent)
    {
    }

    public function getName(): string { return 'kofi'; }

    public function getTitle(): string { return 'Ko-fi'; }

    public function capabilities(): Capabilities
    {
        return new Capabilities(profile: true, key: false, terms: ['https://ko-fi.com/terms']);
    }

    public function profile(?string $account = null): Profile
    {
        $account ??= $this->account;
        $data = Json::request($this->http, 'GET', 'https://example.org/'.rawurlencode($account).'.json',
            ['headers' => ['User-Agent' => $this->userAgent]], 'Ko-fi', "the account $account");

        return new Profile('kofi', Json::string($data, 'handle') ?? $account, Json::string($data, 'name'));
    }
}
```

The address and the fields are placeholders: a real provider reads the platform's documented
answer. Ko-fi has no package; the skeleton shows the shape only.

## What the three providers keep to

- A call per question, and the account's first answer kept in the object when the profile and
  the tiers come from the same query (GitHub, Open Collective).
- `sponsors()` pages until the end or the limit, never asking for more than the limit leaves.
- A missing account is a `ProviderException` naming it (`no such account`, `no such
  collective`, `no GitHub Sponsors listing`), never an empty profile.
- What the platform does not say stays `null`: no guessed income, no computed goal unless both
  amounts are given (Liberapay's percent).

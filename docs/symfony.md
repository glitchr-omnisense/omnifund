---
title: Symfony
order: 15
---

# Symfony

There is no Omnifund bundle, and omnibridge/symfony has no Omnifund wiring yet: a Symfony
application declares the registry as a service of its own. The factories take the application's
`http_client` by autowiring (`Symfony\Contracts\HttpClient\HttpClientInterface`).

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true

    Omnifund\GitHubSponsors\GitHubSponsorsFactory: ~
    Omnifund\OpenCollective\OpenCollectiveFactory: ~
    Omnifund\Liberapay\LiberapayFactory: ~

    Omnifund\Registry:
        arguments:
            $factories:
                - '@Omnifund\GitHubSponsors\GitHubSponsorsFactory'
                - '@Omnifund\OpenCollective\OpenCollectiveFactory'
                - '@Omnifund\Liberapay\LiberapayFactory'
            $config:
                github: { factory: github-sponsors, options: { account: glitchr-studio, token: '%env(default::GITHUB_SPONSORS_TOKEN)%' } }
                oc: { factory: opencollective, options: { account: webpack } }
                liberapay: { factory: liberapay, options: { account: Liberapay } }
```

```sh
# .env.local, or bin/console secrets:set
GITHUB_SPONSORS_TOKEN=github_pat_...
```

Then inject the registry:

```php
use Omnifund\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SponsorsBand
{
    public function __construct(private readonly Registry $funding, private readonly CacheInterface $cache)
    {
    }

    /** @return list<array{name: string, picture: ?string, page: ?string}> */
    public function sponsors(): array
    {
        return $this->cache->get('sponsors.github', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return array_map(static fn ($s) => ['name' => $s->label(), 'picture' => $s->avatarUrl, 'page' => $s->url],
                $this->funding->sponsors('github')->sponsors());
        });
    }
}
```

Nothing is built when the container compiles: a provider is built, and its required options
checked, the first time the registry is asked for it. A token left empty
(`%env(default::...)%` with the variable unset) shows then, as an `InvalidConfigException`.

To give each provider its own HTTP client options (a timeout, a scoped client), declare a scoped
client in `framework.http_client.scoped_clients` and bind it to the factory's `$http` argument.

This page was written from the classes' constructors; it was not run in a Symfony kernel.

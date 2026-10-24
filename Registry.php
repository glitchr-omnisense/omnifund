<?php

namespace Omnifund;

use Omnifund\Exception\InvalidConfigException;
use Omnifund\Exception\NotSupportedException;

/**
 * The application's funding providers by name, each built once from its
 * factory and options:
 *
 *   $registry = new Registry([new GitHubSponsorsFactory($http), new LiberapayFactory($http)], [
 *       'github' => ['factory' => 'github-sponsors', 'options' => ['account' => 'glitchr-studio', 'token' => '...']],
 *       'liberapay' => ['factory' => 'liberapay', 'options' => ['account' => 'Liberapay']],
 *   ]);
 *
 *   $registry->profile('liberapay')->profile()->sponsors;
 *   $registry->sponsors('github')->sponsors();
 */
final class Registry
{
    /** @var array<string, ProviderFactoryInterface> */
    private array $factories = [];

    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                     $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): ProviderInterface
    {
        return $this->providers[$name] ??= $this->factory($name)->create($this->config[$name]['options'] ?? []);
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    /** The provider, as the account's profile. */
    public function profile(string $name): ProfileInterface
    {
        $provider = $this->get($name);

        return $provider instanceof ProfileInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'show a profile');
    }

    /** The provider, as the account's public sponsors. */
    public function sponsors(string $name): SponsorsInterface
    {
        $provider = $this->get($name);

        return $provider instanceof SponsorsInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'list sponsors');
    }

    /** The provider, as the tiers the account offers. */
    public function tiers(string $name): TiersInterface
    {
        $provider = $this->get($name);

        return $provider instanceof TiersInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'list tiers');
    }

    /** @return array<string, mixed> the options a provider is configured with */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /** @return list<string> the configured providers' names */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $name): ProviderFactoryInterface
    {
        $provider = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" funding provider; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$provider['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" funding provider; installed: %s.', $provider['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }
}

<?php

namespace Omnifund;

/** Builds a provider from its options (an account, a token...). */
interface ProviderFactoryInterface
{
    /** The name providers are configured with: "github-sponsors", "liberapay"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): ProviderInterface;
}

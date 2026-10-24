<?php

namespace Omnifund\Exception;

/** The provider answered, and not with what was asked: an error status, an error in a GraphQL answer, no such account. */
final class ProviderException extends \RuntimeException implements OmnifundException
{
    public static function answered(string $provider, string $what, int|string $status): self
    {
        return new self(\sprintf('%s answered %s for %s.', $provider, (string) $status, $what));
    }
}

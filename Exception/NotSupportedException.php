<?php

namespace Omnifund\Exception;

/** A provider asked for what it cannot give: the sponsors of a platform that keeps them private. */
final class NotSupportedException extends \LogicException implements OmnifundException
{
    public static function capability(object $provider, string $name, string $capability): self
    {
        return new self(\sprintf('The "%s" funding provider (%s) does not %s.', $name, $provider::class, $capability));
    }
}

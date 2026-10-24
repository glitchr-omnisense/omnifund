<?php

namespace Omnifund;

/**
 * One funding platform, configured for an account: GitHub Sponsors, Open
 * Collective, Liberapay... Its Capabilities say what it can be asked - the
 * account's profile (ProfileInterface), its public sponsors
 * (SponsorsInterface), the tiers it offers (TiersInterface) - and whose
 * terms apply.
 */
interface ProviderInterface
{
    /** The factory's name: "github-sponsors", "opencollective", "liberapay"... */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}

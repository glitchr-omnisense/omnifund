<?php

namespace Omnifund;

/** What a provider can be asked, said before it is asked anything, and whose terms apply. */
final readonly class Capabilities
{
    /**
     * @param bool         $profile  the account's profile (ProfileInterface)
     * @param bool         $sponsors its public sponsors (SponsorsInterface)
     * @param bool         $tiers    the tiers it offers (TiersInterface)
     * @param bool         $key      whether a key or token is needed to read it
     * @param list<string> $terms    the terms whoever runs it must follow (URLs)
     */
    public function __construct(
        public bool $profile = false,
        public bool $sponsors = false,
        public bool $tiers = false,
        public bool $key = false,
        public array $terms = [],
    ) {
    }

    /** @return list<string> what it can be asked, by contract: profile, sponsors, tiers */
    public function contracts(): array
    {
        return array_keys(array_filter(['profile' => $this->profile, 'sponsors' => $this->sponsors, 'tiers' => $this->tiers]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['contracts' => $this->contracts(), 'profile' => $this->profile, 'sponsors' => $this->sponsors, 'tiers' => $this->tiers, 'key' => $this->key, 'terms' => $this->terms];
    }
}

<?php

namespace Omnifund\Tests;

use Omnifund\Capabilities;
use Omnifund\Exception\InvalidConfigException;
use Omnifund\Exception\NotSupportedException;
use Omnifund\Model\Profile;
use Omnifund\Model\Sponsor;
use Omnifund\ProfileInterface;
use Omnifund\ProviderFactory;
use Omnifund\Config;
use Omnifund\ProviderInterface;
use Omnifund\Registry;
use Omnifund\Testing\ArrayFactory;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testProvidersByNameBuiltOnce(): void
    {
        $registry = new Registry([new ArrayFactory()], ['fixed' => ['factory' => 'array', 'options' => ['profile' => new Profile('array', 'me'), 'sponsors' => [new Sponsor('a'), new Sponsor('b')]]]]);

        self::assertSame($registry->get('fixed'), $registry->get('fixed'));
        self::assertSame('me', $registry->profile('fixed')->profile()->handle);
        self::assertCount(1, $registry->sponsors('fixed')->sponsors(limit: 1));
        self::assertSame([], $registry->tiers('fixed')->tiers());
        self::assertSame(['fixed'], $registry->names());
        self::assertSame(['array'], $registry->factories());
        self::assertTrue($registry->has('fixed'));
        self::assertFalse($registry->has('other'));
    }

    public function testAProviderWithoutTheCapabilityAskedFor(): void
    {
        $registry = new Registry([new ProfileOnlyFactory()], ['p' => ['factory' => 'profile-only']]);

        self::assertSame('profile-only', $registry->profile('p')->profile()->provider);
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('The "p" funding provider (Omnifund\Tests\ProfileOnly) does not list sponsors.');
        $registry->sponsors('p');
    }

    public function testAProviderNotConfigured(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No "x" funding provider; configured: none.');
        (new Registry([], []))->get('x');
    }

    public function testARequiredOptionMissing(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "array" provider needs: profile.');
        (new Registry([new ArrayFactory()], ['fixed' => ['factory' => 'array']]))->get('fixed');
    }
}

final class ProfileOnly implements ProfileInterface
{
    public function getName(): string
    {
        return 'profile-only';
    }

    public function getTitle(): string
    {
        return 'Profile only';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(profile: true);
    }

    public function profile(?string $account = null): Profile
    {
        return new Profile('profile-only', $account ?? 'someone');
    }
}

final class ProfileOnlyFactory extends ProviderFactory
{
    protected function populate(Config $c): void
    {
        $c->defaults(['omnifund.factory_name' => 'profile-only']);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new ProfileOnly();
    }
}

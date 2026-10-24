<?php

namespace Omnifund\Tests;

use Omnifund\Registry;
use PHPUnit\Framework\TestCase;

/**
 * omniscient/omnifund with no framework: in PHP processes of their own
 * (this one has loaded PHPUnit), the harness's bare script builds a
 * registry, reads a profile, sponsors and tiers - and every class of the
 * package is loaded. Nothing may come from outside the package but what it
 * requires: Symfony's HTTP client contracts (interfaces only) - no
 * framework, no HTTP client, no application, even in the harness where they
 * are installed beside it.
 */
final class BareTest extends TestCase
{
    private const FOREIGN = '~^(?:Symfony\\\\(?!Contracts\\\\HttpClient\\\\|Polyfill\\\\)|(?:Illuminate|Laravel|Doctrine|Twig|League|GuzzleHttp|Nyholm|Http|Omnibridge|Base|App)\\\\)~';
    private const ALLOWED_FILES = '~/vendor/(?:symfony/http-client-contracts|composer)/|/vendor/autoload\.php$~';

    public function testARegistryReadByHandLoadsNothingButOmnifundAndWhatItRequires(): void
    {
        $report = self::php([__DIR__.'/../docker/harness/bin/bare']);

        self::assertSame('Glitch Art', self::at($report, 'profile', 'name'));
        self::assertSame(['amount' => ['cents' => 1200, 'currency' => 'EUR', 'decimal' => '12.00'], 'period' => 'week'], self::at($report, 'profile', 'income'));
        self::assertSame('52.00', $report['monthly'], '12 a week, 52 weeks a year: 52 a month');
        self::assertSame('100.00', self::at($report, 'profile', 'goal', 'amount', 'decimal'));
        self::assertSame(['Ada', 'ACME'], $report['sponsors']);
        self::assertSame([['Backer', '5.00', 'month'], ['Once', null, 'once']], $report['tiers']);
        self::assertSame(['profile', 'sponsors', 'tiers'], $report['capabilities']);
        self::assertSame('No "missing" factory for the "nowhere" funding provider; installed: array.', $report['missing']);
        self::assertSame([42, '2016-10-15', null], $report['read']);

        self::assertContains('Omnifund\Registry', self::strings(self::at($report, 'loaded', 'symbols')));
        self::assertSame([], self::foreign(self::strings(self::at($report, 'loaded', 'symbols'))), 'no class of a framework, an HTTP client or an application');
        self::assertSame([], self::outside(self::strings(self::at($report, 'loaded', 'files')), self::strings(self::at($report, 'loaded', 'eager'))), 'no file but the package and what it requires');
    }

    public function testEveryClassLoadsWithTheRequiredPackagesAlone(): void
    {
        $report = self::php([__DIR__.'/Fixtures/load-all.php', self::autoload(), \dirname(__DIR__)]);

        self::assertGreaterThan(20, \count(self::strings($report['loaded'])));
        self::assertContains('Omnifund\Http\Json', self::strings($report['loaded']));
        self::assertSame([], self::foreign(self::strings($report['symbols'])));
        self::assertSame([], self::outside(self::strings($report['files']), self::strings($report['eager'])));
    }

    /** The check is not blind: an HTTP client's class, once loaded, is seen. */
    public function testTheReportSeesWhatIsLoaded(): void
    {
        if (!class_exists(\Symfony\Component\HttpClient\MockHttpClient::class)) {
            self::markTestSkipped('symfony/http-client is not installed.');
        }
        $report = self::php([__DIR__.'/Fixtures/load-all.php', self::autoload(), \dirname(__DIR__), \Symfony\Component\HttpClient\MockHttpClient::class]);

        self::assertContains(\Symfony\Component\HttpClient\MockHttpClient::class, self::foreign(self::strings($report['symbols'])));
        self::assertNotEmpty(preg_grep('~/symfony/http-client/~', self::outside(self::strings($report['files']), self::strings($report['eager']))));
    }

    /** @param array<mixed> $report */
    private static function at(array $report, string ...$path): mixed
    {
        foreach ($path as $key) {
            $report = \is_array($report) && \array_key_exists($key, $report) ? $report[$key] : null;
        }

        return $report;
    }

    /** @return list<string> the strings of a list (null and other values left out) */
    private static function strings(mixed $list): array
    {
        return \is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    /**
     * @param list<string> $symbols
     *
     * @return list<string>
     */
    private static function foreign(array $symbols): array
    {
        return array_values(array_filter($symbols, static fn (string $symbol) => 1 === preg_match(self::FOREIGN, $symbol)));
    }

    /**
     * The files loaded from outside the package but those allowed - and those
     * Composer includes for every package at start (autoload "files").
     *
     * @param list<string> $files
     * @param list<string> $eager
     *
     * @return list<string>
     */
    private static function outside(array $files, array $eager): array
    {
        $root = realpath(\dirname(__DIR__)).'/';
        $eager = array_map('dirname', array_filter($eager));

        return array_values(array_filter($files, static fn (string $file) => (!str_starts_with($file, $root) || str_starts_with($file, $root.'vendor/')) && !\in_array(\dirname($file), $eager, true) && !preg_match(self::ALLOWED_FILES, $file)));
    }

    /**
     * The autoloader that knows omnifund and its tests - the package's own or
     * its harness's: installed in an application, whose vendor/ loads what
     * the application needs at start, the check says nothing of omnifund.
     */
    private static function autoload(): string
    {
        $package = (string) realpath(\dirname(__DIR__));
        foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $vendor => $loader) {
            if ($loader->findFile(Registry::class) && $loader->findFile(self::class)) {
                if (str_starts_with($package.'/', (string) realpath($vendor).'/')) {
                    self::markTestSkipped('omnifund is installed in an application here: the bare check is the package\'s own.');
                }

                return $vendor.'/autoload.php';
            }
        }
        self::markTestSkipped('No autoloader knows omnifund\'s tests.');
    }

    /**
     * @param list<string> $arguments
     *
     * @return array<string, mixed>
     */
    private static function php(array $arguments): array
    {
        $process = proc_open([\PHP_BINARY, ...$arguments], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['OMNIFUND_AUTOLOAD' => self::autoload()] + getenv());
        self::assertIsResource($process);
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        $status = proc_close($process);
        $report = json_decode($out, true);
        self::assertSame(0, $status, $err.$out);
        self::assertIsArray($report, $err.$out);

        /** @var array<string, mixed> $report */
        return $report;
    }
}

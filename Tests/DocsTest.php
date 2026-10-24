<?php

namespace Omnifund\Tests;

use PHPUnit\Framework\TestCase;

/** docs/installation.md's PHP block, run as it is written (its autoloader the one of this run). */
final class DocsTest extends TestCase
{
    public function testTheInstallationPageRunsAsWritten(): void
    {
        $text = (string) file_get_contents(__DIR__.'/../docs/installation.md');
        self::assertSame(1, preg_match('~```php\n(.*?)```~s', $text, $match));
        $block = $match[1] ?? '';
        $autoload = null;
        foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $vendor => $loader) {
            if ($loader->findFile('Omnifund\Liberapay\LiberapayFactory') && $loader->findFile('Symfony\Component\HttpClient\MockHttpClient')) {
                $autoload = $vendor.'/autoload.php';
                break;
            }
        }
        if (null === $autoload) {
            self::markTestSkipped('omnifund/liberapay and symfony/http-client are not installed beside omnifund (they are in the harness).');
        }
        $code = str_replace("require 'vendor/autoload.php';", 'require '.var_export($autoload, true).';', $block, $replaced);
        self::assertSame(1, $replaced);
        $script = (string) tempnam(sys_get_temp_dir(), 'omnifund-docs');
        file_put_contents($script, $code);
        try {
            $process = proc_open([\PHP_BINARY, $script], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            $out = (string) stream_get_contents($pipes[1]);
            $err = (string) stream_get_contents($pipes[2]);
            self::assertSame(0, proc_close($process), $err.$out);
        } finally {
            unlink($script);
        }

        $lines = ['1782 patrons', '2757.21 EUR a month', '63% of the goal'];
        self::assertSame(implode("\n", $lines)."\n", $out);
        foreach ($lines as $line) {
            self::assertStringContainsString("\n// $line\n", $block, 'the page says what it prints');
        }
    }
}

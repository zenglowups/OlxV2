<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class EnvironmentSetupTest extends TestCase
{
    public function test_first_run_generates_credentials_and_repeat_run_preserves_them(): void
    {
        $directory = sys_get_temp_dir().'/olxv2-env-test-'.bin2hex(random_bytes(8));
        mkdir($directory.'/scripts', 0700, true);
        copy(dirname(__DIR__, 2).'/scripts/setup-env.php', $directory.'/scripts/setup-env.php');
        copy(dirname(__DIR__, 2).'/.env.example', $directory.'/.env.example');
        try {
            $first = new Process([PHP_BINARY, $directory.'/scripts/setup-env.php']);
            $first->mustRun();
            $contents = file_get_contents($directory.'/.env');
            $this->assertMatchesRegularExpression('/APP_KEY=base64:[A-Za-z0-9+\/=]+/', $contents);
            $this->assertMatchesRegularExpression('/DB_PASSWORD=[a-f0-9]{48}/', $contents);
            $this->assertMatchesRegularExpression('/AWS_SECRET_ACCESS_KEY=[a-f0-9]{64}/', $contents);
            $second = new Process([PHP_BINARY, $directory.'/scripts/setup-env.php']);
            $second->mustRun();
            $this->assertSame($contents, file_get_contents($directory.'/.env'));
            $this->assertStringContainsString('preserved', $second->getOutput());
            preg_match('/DB_PASSWORD=(.+)/', $contents, $matches);
            $this->assertStringNotContainsString($matches[1], $first->getOutput());
        } finally {
            foreach (['.env', '.env.example', 'scripts/setup-env.php'] as $file) {
                if (is_file($directory.'/'.$file)) {
                    unlink($directory.'/'.$file);
                }
            }
            rmdir($directory.'/scripts');
            rmdir($directory);
        }
    }
}

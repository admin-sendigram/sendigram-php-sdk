<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests;

use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Configuration;
use Sendigram\Ems\Client\Exception\ConfigurationException;

final class ConfigurationTest extends TestCase
{
    public function testDefaultFactoryProducesSensibleDefaults(): void
    {
        $cfg = Configuration::default('tok-123');

        $this->assertSame('tok-123', $cfg->token);
        $this->assertSame('https://ems.sendigram.com/open-api/v1/', $cfg->baseUrl);
        $this->assertSame(30, $cfg->timeout);
        $this->assertStringStartsWith('ems-php/', $cfg->userAgent);
        $this->assertStringContainsString('PHP '.PHP_VERSION, $cfg->userAgent);
    }

    public function testEmptyTokenRejected(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('token must be a non-empty string');

        Configuration::default('');
    }

    public function testWhitespaceTokenRejected(): void
    {
        $this->expectException(ConfigurationException::class);

        Configuration::default("   \t  ");
    }

    public function testWithBaseUrlEnforcesTrailingSlash(): void
    {
        $cfg = Configuration::default('t')
            ->withBaseUrl('https://ems.sendigram.com/open-api/v1');

        $this->assertSame('https://ems.sendigram.com/open-api/v1/', $cfg->baseUrl);
    }

    public function testWithBaseUrlKeepsExistingSlash(): void
    {
        $cfg = Configuration::default('t')
            ->withBaseUrl('https://ems.sendigram.com/open-api/v1/');

        $this->assertSame('https://ems.sendigram.com/open-api/v1/', $cfg->baseUrl);
    }

    public function testWithBaseUrlRejectsRelativeUrl(): void
    {
        $this->expectException(ConfigurationException::class);

        Configuration::default('t')->withBaseUrl('/open-api/v1');
    }

    public function testWithBaseUrlRejectsEmpty(): void
    {
        $this->expectException(ConfigurationException::class);

        Configuration::default('t')->withBaseUrl('');
    }

    public function testWithTimeoutRequiresPositive(): void
    {
        $this->expectException(ConfigurationException::class);

        Configuration::default('t')->withTimeout(0);
    }

    public function testWithTimeoutRejectsNegative(): void
    {
        $this->expectException(ConfigurationException::class);

        Configuration::default('t')->withTimeout(-5);
    }

    public function testWithUserAgentRejectsEmpty(): void
    {
        $this->expectException(ConfigurationException::class);

        Configuration::default('t')->withUserAgent('');
    }

    public function testWithUserAgentStoresValue(): void
    {
        $cfg = Configuration::default('t')->withUserAgent('my-app/1.0');

        $this->assertSame('my-app/1.0', $cfg->userAgent);
    }

    public function testFluentSettersAreImmutable(): void
    {
        $a = Configuration::default('t');
        $b = $a->withTimeout(60);
        $c = $a->withBaseUrl('https://ems.sendigram.com/open-api/v1/');
        $d = $a->withUserAgent('my-app/1.0');

        $this->assertNotSame($a, $b);
        $this->assertNotSame($a, $c);
        $this->assertNotSame($a, $d);
        $this->assertSame(30, $a->timeout);
        $this->assertSame(60, $b->timeout);
        $this->assertSame(Configuration::DEFAULT_BASE_URL, $a->baseUrl);
        $this->assertStringStartsWith('ems-php/', $a->userAgent);
    }
}

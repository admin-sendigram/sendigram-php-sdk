<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Exception\ApiException;
use Sendigram\Ems\Client\Exception\BadRequestException;
use Sendigram\Ems\Client\Exception\ConfigurationException;
use Sendigram\Ems\Client\Exception\EmsException;
use Sendigram\Ems\Client\Exception\ForbiddenException;
use Sendigram\Ems\Client\Exception\NetworkException;
use Sendigram\Ems\Client\Exception\NotFoundException;
use Sendigram\Ems\Client\Exception\RateLimitException;
use Sendigram\Ems\Client\Exception\SerializationException;
use Sendigram\Ems\Client\Exception\ServerException;
use Sendigram\Ems\Client\Exception\UnauthorizedException;
use Sendigram\Ems\Client\Exception\ValidationException;

final class ExceptionHierarchyTest extends TestCase
{
    public function testBaseExtendsRuntime(): void
    {
        $e = new EmsException('x');
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }

    /** @dataProvider sdkBranchProvider */
    public function testSdkBranchExtendsBase(string $class): void
    {
        $e = new $class('x');
        $this->assertInstanceOf(EmsException::class, $e);
    }

    /** @return array<int, array{0: class-string}> */
    public static function sdkBranchProvider(): array
    {
        return [
            [ConfigurationException::class],
            [NetworkException::class],
            [SerializationException::class],
        ];
    }

    public function testApiExceptionCarriesContext(): void
    {
        $e = new ApiException(
            statusCode: 500,
            responseBody: '{"message":"boom"}',
            responseHeaders: ['Content-Type' => ['application/json']],
            decodedBody: ['message' => 'boom'],
            requestMethod: 'GET',
            requestUri: 'https://ems.sendigram.com/open-api/v1/contacts',
        );

        $this->assertInstanceOf(EmsException::class, $e);
        $this->assertSame(500, $e->statusCode);
        $this->assertSame('{"message":"boom"}', $e->responseBody);
        $this->assertSame(['Content-Type' => ['application/json']], $e->responseHeaders);
        $this->assertSame(['message' => 'boom'], $e->decodedBody);
        $this->assertSame('GET', $e->requestMethod);
        $this->assertSame('https://ems.sendigram.com/open-api/v1/contacts', $e->requestUri);
        $this->assertSame('EMS API returned HTTP 500', $e->getMessage());
    }

    public function testApiExceptionCustomMessage(): void
    {
        $e = new ApiException(
            statusCode: 500,
            responseBody: '',
            responseHeaders: [],
            decodedBody: null,
            requestMethod: 'GET',
            requestUri: '/',
            message: 'Custom',
        );
        $this->assertSame('Custom', $e->getMessage());
    }

    /** @dataProvider statusBranchProvider */
    public function testStatusBranchExtendsApi(string $class, int $expectedStatus): void
    {
        $e = new $class(
            statusCode: $expectedStatus,
            responseBody: '',
            responseHeaders: [],
            decodedBody: null,
            requestMethod: 'GET',
            requestUri: '/',
        );
        $this->assertInstanceOf(ApiException::class, $e);
        $this->assertSame($expectedStatus, $e->statusCode);
    }

    /** @return array<int, array{0: class-string, 1: int}> */
    public static function statusBranchProvider(): array
    {
        return [
            [BadRequestException::class,   400],
            [UnauthorizedException::class, 401],
            [ForbiddenException::class,    403],
            [NotFoundException::class,     404],
            [ServerException::class,       500],
            [ValidationException::class,   422],
            [RateLimitException::class,    429],
        ];
    }

    public function testValidationExceptionExposesErrors(): void
    {
        $e = new ValidationException(
            statusCode: 422,
            responseBody: '{"message":"The given data was invalid.","errors":{"email":["bad"]}}',
            responseHeaders: [],
            decodedBody: [
                'message' => 'The given data was invalid.',
                'errors' => ['email' => ['bad']],
            ],
            requestMethod: 'POST',
            requestUri: '/contacts',
        );

        $this->assertSame(['email' => ['bad']], $e->errors());
    }

    public function testValidationExceptionReturnsEmptyArrayWhenAbsent(): void
    {
        $e = new ValidationException(
            statusCode: 422,
            responseBody: '',
            responseHeaders: [],
            decodedBody: null,
            requestMethod: 'POST',
            requestUri: '/contacts',
        );

        $this->assertSame([], $e->errors());
    }

    public function testValidationExceptionReturnsEmptyArrayWhenErrorsIsScalar(): void
    {
        $e = new ValidationException(
            statusCode: 422,
            responseBody: '{"errors":"invalid"}',
            responseHeaders: [],
            decodedBody: ['errors' => 'invalid'],
            requestMethod: 'POST',
            requestUri: '/contacts',
        );

        $this->assertSame([], $e->errors());
    }

    public function testRateLimitParsesRetryAfter(): void
    {
        $e = new RateLimitException(
            statusCode: 429,
            responseBody: '',
            responseHeaders: ['Retry-After' => ['37']],
            decodedBody: null,
            requestMethod: 'GET',
            requestUri: '/contacts',
        );

        $this->assertSame(37, $e->retryAfter());
    }

    public function testRateLimitParsesLowercaseRetryAfter(): void
    {
        $e = new RateLimitException(
            statusCode: 429,
            responseBody: '',
            responseHeaders: ['retry-after' => ['30']],
            decodedBody: null,
            requestMethod: 'GET',
            requestUri: '/contacts',
        );

        $this->assertSame(30, $e->retryAfter());
    }

    public function testRateLimitReturnsNullWhenMissing(): void
    {
        $e = new RateLimitException(
            statusCode: 429,
            responseBody: '',
            responseHeaders: [],
            decodedBody: null,
            requestMethod: 'GET',
            requestUri: '/contacts',
        );

        $this->assertNull($e->retryAfter());
    }

    public function testRateLimitIgnoresNonNumeric(): void
    {
        $e = new RateLimitException(
            statusCode: 429,
            responseBody: '',
            responseHeaders: ['Retry-After' => ['Wed, 21 Oct 2026 07:28:00 GMT']],
            decodedBody: null,
            requestMethod: 'GET',
            requestUri: '/contacts',
        );

        $this->assertNull($e->retryAfter());
    }
}

<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Tests\Exception;

use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Sendigram\Ems\Client\Exception\ApiException;
use Sendigram\Ems\Client\Exception\BadRequestException;
use Sendigram\Ems\Client\Exception\ForbiddenException;
use Sendigram\Ems\Client\Exception\NotFoundException;
use Sendigram\Ems\Client\Exception\RateLimitException;
use Sendigram\Ems\Client\Exception\SerializationException;
use Sendigram\Ems\Client\Exception\ServerException;
use Sendigram\Ems\Client\Exception\UnauthorizedException;
use Sendigram\Ems\Client\Exception\ValidationException;
use Sendigram\Ems\Client\Http\ResponseParser;
use Sendigram\Ems\Client\Model\Contact;
use Sendigram\Ems\Client\Serializer\ObjectSerializer;

final class ResponseParserTest extends TestCase
{
    private ResponseParser $parser;
    private HttpFactory $http;

    protected function setUp(): void
    {
        $this->parser = new ResponseParser(new ObjectSerializer());
        $this->http = new HttpFactory();
    }

    public function testParsesSuccessfulBodyIntoDto(): void
    {
        $response = $this->http->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->http->createStream(json_encode([
                'data' => [
                    'id' => 42,
                    'email' => 'a@b.c',
                    'first_name' => null,
                    'last_name' => null,
                    'gender' => null,
                    'status' => null,
                    'date_of_birth' => null,
                    'country_id' => null,
                    'city_id' => null,
                    'created_at' => '2026-01-01T00:00:00+00:00',
                ],
            ], JSON_THROW_ON_ERROR)));

        $contact = $this->parser->parse($response, Contact::class, 'GET', '/contacts/42');

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame(42, $contact->id);
    }

    public function testParsesUnwrappedSuccessfulBody(): void
    {
        // Some endpoints return DTO directly (not wrapped in 'data').
        $response = $this->http->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->http->createStream(json_encode([
                'id' => 7,
                'email' => 'x@y.z',
                'first_name' => null, 'last_name' => null, 'gender' => null,
                'status' => null,  'date_of_birth' => null,
                'country_id' => null, 'city_id' => null,
                'created_at' => '2026-01-01T00:00:00+00:00',
            ], JSON_THROW_ON_ERROR)));

        $contact = $this->parser->parse($response, Contact::class, 'GET', '/contacts/7');

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertSame(7, $contact->id);
    }

    public function testReturnsNullWhenNoModelExpected(): void
    {
        $response = $this->http->createResponse(200)
            ->withBody($this->http->createStream(json_encode(['message' => 'ok'], JSON_THROW_ON_ERROR)));

        $result = $this->parser->parse($response, null, 'DELETE', '/contacts/1');

        $this->assertNull($result);
    }

    public function testReturnsNullOn204EmptyBody(): void
    {
        $response = $this->http->createResponse(204)
            ->withBody($this->http->createStream(''));

        $result = $this->parser->parse($response, null, 'DELETE', '/contacts/1');

        $this->assertNull($result);
    }

    public function testInvalidJsonThrowsSerializationException(): void
    {
        $response = $this->http->createResponse(200)
            ->withBody($this->http->createStream('not json'));

        $this->expectException(SerializationException::class);

        $this->parser->parse($response, Contact::class, 'GET', '/contacts/1');
    }

    /**
     * @dataProvider statusMapProvider
     *
     * @param class-string<ApiException> $class
     */
    public function testMapsStatusCodeToException(int $status, string $class): void
    {
        $response = $this->http->createResponse($status)
            ->withBody($this->http->createStream(json_encode(['message' => 'x'], JSON_THROW_ON_ERROR)));

        try {
            $this->parser->parse($response, Contact::class, 'GET', '/contacts/1');
            $this->fail('expected exception');
        } catch (ApiException $e) {
            $this->assertInstanceOf($class, $e);
            $this->assertSame($status, $e->statusCode);
            $this->assertSame('GET', $e->requestMethod);
            $this->assertSame('/contacts/1', $e->requestUri);
        }
    }

    /**
     * @return array<int, array{0: int, 1: class-string<ApiException>}>
     */
    public static function statusMapProvider(): array
    {
        return [
            [400, BadRequestException::class],
            [401, UnauthorizedException::class],
            [403, ForbiddenException::class],
            [404, NotFoundException::class],
            [422, ValidationException::class],
            [429, RateLimitException::class],
            [500, ServerException::class],
            [503, ServerException::class],
            // Unknown 4xx falls back to ApiException
            [418, ApiException::class],
        ];
    }

    public function testValidationExceptionCarriesErrors(): void
    {
        $body = json_encode([
            'message' => 'The given data was invalid.',
            'errors' => ['email' => ['The email must be a valid email address.']],
        ], JSON_THROW_ON_ERROR);

        $response = $this->http->createResponse(422)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->http->createStream($body));

        try {
            $this->parser->parse($response, Contact::class, 'POST', '/contacts');
            $this->fail('expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['email' => ['The email must be a valid email address.']],
                $e->errors(),
            );
        }
    }

    public function testRateLimitParsesHeader(): void
    {
        $response = $this->http->createResponse(429)
            ->withHeader('Retry-After', '90')
            ->withBody($this->http->createStream(''));

        try {
            $this->parser->parse($response, Contact::class, 'GET', '/contacts');
            $this->fail('expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(90, $e->retryAfter());
        }
    }

    public function testNonJsonErrorBodyStillThrowsWithNullDecodedBody(): void
    {
        $response = $this->http->createResponse(500)
            ->withBody($this->http->createStream('Internal Server Error'));

        try {
            $this->parser->parse($response, Contact::class, 'GET', '/contacts');
            $this->fail('expected ServerException');
        } catch (ServerException $e) {
            $this->assertNull($e->decodedBody);
            $this->assertSame('Internal Server Error', $e->responseBody);
        }
    }
}

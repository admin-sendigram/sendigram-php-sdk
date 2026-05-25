<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client;

use GuzzleHttp\Client as GuzzleClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Sendigram\Ems\Client\Http\HttpTransport;
use Sendigram\Ems\Client\Http\RequestBuilder;
use Sendigram\Ems\Client\Http\ResponseParser;
use Sendigram\Ems\Client\Resources\AbstractResource;
use Sendigram\Ems\Client\Resources\ContactsResource;
use Sendigram\Ems\Client\Serializer\ObjectSerializer;

/**
 * Main entry point of the EMS PHP SDK.
 *
 * Usage:
 *
 *     $client = new EmsClient('eyJ...');
 *     $contact = $client->contacts->get(42);
 *
 * Resources are exposed as **lazy properties** via `__get()`. Once accessed,
 * the resource instance is cached on the client for the rest of its lifetime.
 *
 * To add a new resource later, register it in {@see EmsClient::RESOURCE_MAP}.
 *
 * @property ContactsResource $contacts
 */
final class EmsClient
{
    /** Map of property name → resource class. The class must extend {@see AbstractResource}. */
    private const RESOURCE_MAP = [
        'contacts' => ContactsResource::class,
    ];

    private readonly Configuration $config;
    private readonly HttpTransport $transport;
    private readonly RequestBuilder $requestBuilder;
    private readonly ResponseParser $responseParser;
    private readonly ObjectSerializer $serializer;

    /** @var array<string, AbstractResource> */
    private array $resources = [];

    public function __construct(
        Configuration|string $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->config = is_string($config) ? Configuration::default($config) : $config;

        $httpClient ??= new GuzzleClient(['timeout' => $this->config->timeout]);
        $requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();

        $this->serializer = new ObjectSerializer();
        $this->transport = new HttpTransport($httpClient);
        $this->requestBuilder = new RequestBuilder($this->config, $requestFactory, $streamFactory);
        $this->responseParser = new ResponseParser($this->serializer);
    }

    public function __get(string $name): AbstractResource
    {
        if (!array_key_exists($name, self::RESOURCE_MAP)) {
            throw new \InvalidArgumentException("Unknown EMS resource: {$name}");
        }

        return $this->resources[$name] ??= new (self::RESOURCE_MAP[$name])(
            $this->transport,
            $this->requestBuilder,
            $this->responseParser,
            $this->serializer,
        );
    }
}

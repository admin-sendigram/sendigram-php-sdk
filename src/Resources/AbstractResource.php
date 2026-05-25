<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Resources;

use Sendigram\Ems\Client\Http\HttpTransport;
use Sendigram\Ems\Client\Http\RequestBuilder;
use Sendigram\Ems\Client\Http\ResponseParser;
use Sendigram\Ems\Client\Serializer\ObjectSerializer;

/**
 * Base class for every resource (`ContactsResource`, future `CampaignsResource`,
 * …). Holds the four collaborators each resource needs; concrete subclasses
 * use the protected accessors to build and send requests.
 */
abstract class AbstractResource
{
    public function __construct(
        protected readonly HttpTransport $transport,
        protected readonly RequestBuilder $requestBuilder,
        protected readonly ResponseParser $responseParser,
        protected readonly ObjectSerializer $serializer,
    ) {
    }
}

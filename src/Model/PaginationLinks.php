<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

/** Laravel paginator `links` block. Any field may be null on edge pages. */
final class PaginationLinks
{
    public function __construct(
        public readonly ?string $first,
        public readonly ?string $last,
        public readonly ?string $prev,
        public readonly ?string $next,
    ) {
    }
}

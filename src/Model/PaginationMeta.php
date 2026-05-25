<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

/** Laravel paginator `meta` block. */
final class PaginationMeta
{
    public function __construct(
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly ?int $from,
        public readonly ?int $to,
    ) {
    }
}

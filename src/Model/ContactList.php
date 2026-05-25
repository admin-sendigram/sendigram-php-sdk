<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Model;

/** Paginated wrapper around `Contact[]` returned by `GET /contacts`. */
final class ContactList
{
    /**
     * @param Contact[] $data
     */
    public function __construct(
        public readonly array $data,
        public readonly PaginationMeta $meta,
        public readonly PaginationLinks $links,
    ) {
    }

    /** True iff a further page exists (current page < last page). */
    public function hasNextPage(): bool
    {
        return $this->meta->currentPage < $this->meta->lastPage;
    }
}

<?php

declare(strict_types=1);

/**
 * List the first page of contacts and print them.
 *
 * Run:
 *
 *     EMS_TOKEN=eyJ... php examples/list-contacts.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Sendigram\Ems\Client\EmsClient;
use Sendigram\Ems\Client\Exception\EmsException;

$token = getenv('EMS_TOKEN') ?: throw new RuntimeException('Set EMS_TOKEN');

$client = new EmsClient($token);

try {
    $list = $client->contacts->list(perPage: 10);
} catch (EmsException $e) {
    fwrite(STDERR, "EMS error: {$e->getMessage()}\n");
    exit(1);
}

printf("Page %d of %d (total %d)\n", $list->meta->currentPage, $list->meta->lastPage, $list->meta->total);
foreach ($list->data as $contact) {
    printf("#%d  %s  <%s>\n", $contact->id, trim(($contact->firstName ?? '') . ' ' . ($contact->lastName ?? '')), $contact->email ?? '-');
}

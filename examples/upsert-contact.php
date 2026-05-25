<?php

declare(strict_types=1);

/**
 * Upsert a contact by email and print whether it was created or updated.
 *
 * Run:
 *
 *     EMS_TOKEN=eyJ... php examples/upsert-contact.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Sendigram\Ems\Client\EmsClient;

$token = getenv('EMS_TOKEN') ?: throw new RuntimeException('Set EMS_TOKEN');

$client = new EmsClient($token);

$result = $client->contacts->upsert(
    email:     'alice@example.com',
    firstName: 'Alice',
);

printf(
    "%s contact #%d (%s)\n",
    $result->created ? 'Created' : 'Updated',
    $result->contact->id,
    $result->contact->email ?? '-',
);

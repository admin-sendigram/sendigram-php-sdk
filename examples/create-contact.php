<?php

declare(strict_types=1);

/**
 * Create a contact with named arguments.
 *
 * Run:
 *
 *     EMS_TOKEN=eyJ... php examples/create-contact.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Sendigram\Ems\Client\EmsClient;
use Sendigram\Ems\Client\Exception\ValidationException;
use Sendigram\Ems\Client\Model\Enum\ContactGender;

$token = getenv('EMS_TOKEN') ?: throw new RuntimeException('Set EMS_TOKEN');

$client = new EmsClient($token);

try {
    $contact = $client->contacts->create(
        email:     'john.doe.' . bin2hex(random_bytes(3)) . '@example.com',
        firstName: 'John',
        lastName:  'Doe',
        gender:    ContactGender::MALE,
        country:   'PL',
        city:      'Warsaw',
    );

    printf("Created contact #%d (%s)\n", $contact->id, $contact->email);
} catch (ValidationException $e) {
    fwrite(STDERR, "Validation failed:\n");
    foreach ($e->errors() as $field => $messages) {
        fwrite(STDERR, "  {$field}: " . implode('; ', $messages) . "\n");
    }
    exit(1);
}

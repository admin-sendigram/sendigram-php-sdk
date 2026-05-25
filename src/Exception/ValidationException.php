<?php

declare(strict_types=1);

namespace Sendigram\Ems\Client\Exception;

/**
 * Thrown on HTTP 422 responses (Laravel validation error bag).
 *
 * Use {@see errors()} to retrieve field-level error messages so that calling
 * code can map them back to form inputs.
 */
final class ValidationException extends ApiException
{
    /**
     * Field-keyed error map: `[ 'email' => ['The email must be valid.'] ]`.
     *
     * Returns an empty array when the response body did not contain an
     * `errors` key (which happens for non-Laravel 422 responses).
     *
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        $errors = $this->decodedBody['errors'] ?? [];

        return is_array($errors) ? $errors : [];
    }
}

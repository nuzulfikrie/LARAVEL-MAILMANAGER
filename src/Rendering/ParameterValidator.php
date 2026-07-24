<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering;

use Carbon\Carbon;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmptyCollectionBehavior;
use NuzulFikrieCoder\LaravelMailmanager\Enums\ParameterType;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\InvalidCollectionException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\InvalidParameterValueException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\MissingRequiredParameterException;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\UnknownParameterException;
use Throwable;

final class ParameterValidator
{
    /**
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $parameters
     */
    public function validate(array $schema, array $parameters, bool $strict): void
    {
        foreach ($schema as $key => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $required = (bool) ($definition['required'] ?? false);
            $type = ParameterType::tryFrom((string) ($definition['type'] ?? 'string')) ?? ParameterType::String;
            $present = array_key_exists($key, $parameters);
            $value = $parameters[$key] ?? null;

            if (! $present || $value === null || $value === '') {
                if ($required) {
                    throw new MissingRequiredParameterException("Missing required parameter [{$key}].");
                }

                continue;
            }

            $this->assertType($key, $type, $value, $definition, $strict);
        }

        if ($strict) {
            foreach (array_keys($parameters) as $key) {
                if (! array_key_exists($key, $schema)) {
                    throw new UnknownParameterException("Unknown parameter [{$key}].");
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function assertType(string $key, ParameterType $type, mixed $value, array $definition, bool $strict): void
    {
        match ($type) {
            ParameterType::Collection => $this->assertCollection($key, $value, $definition, $strict),
            ParameterType::Number => $this->assertNumber($key, $value),
            ParameterType::Boolean => $this->assertBoolean($key, $value),
            ParameterType::Date => $this->assertDate($key, $value),
            ParameterType::Url => $this->assertUrl($key, $value),
            ParameterType::String => null,
        };
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function assertCollection(string $key, mixed $value, array $definition, bool $strict): void
    {
        if (! is_array($value)) {
            throw new InvalidCollectionException("Parameter [{$key}] must be an array collection.");
        }

        if ($value === []) {
            $behavior = EmptyCollectionBehavior::tryFrom((string) ($definition['empty_behavior'] ?? 'headers_message'))
                ?? EmptyCollectionBehavior::HeadersMessage;

            if ($behavior === EmptyCollectionBehavior::Fail) {
                throw new InvalidCollectionException("Collection [{$key}] is empty.");
            }

            return;
        }

        $columns = $definition['columns'] ?? [];
        $allowedFields = [];

        if (is_array($columns)) {
            foreach ($columns as $column) {
                if (is_array($column) && isset($column['field'])) {
                    $allowedFields[] = (string) $column['field'];
                }
            }
        }

        foreach ($value as $index => $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }

            if (! is_array($row)) {
                throw new InvalidCollectionException("Collection [{$key}] row [{$index}] must be an array or object.");
            }

            if ($strict && $allowedFields !== []) {
                foreach (array_keys($row) as $field) {
                    if (! in_array((string) $field, $allowedFields, true)) {
                        throw new InvalidCollectionException("Unknown field [{$field}] in collection [{$key}].");
                    }
                }
            }
        }
    }

    private function assertNumber(string $key, mixed $value): void
    {
        if (! is_numeric($value)) {
            throw new InvalidParameterValueException("Parameter [{$key}] must be numeric.");
        }
    }

    private function assertBoolean(string $key, mixed $value): void
    {
        if (! is_bool($value) && ! in_array($value, [0, 1, '0', '1', 'true', 'false'], true)) {
            throw new InvalidParameterValueException("Parameter [{$key}] must be boolean.");
        }
    }

    private function assertDate(string $key, mixed $value): void
    {
        try {
            Carbon::parse($value);
        } catch (Throwable $e) {
            throw new InvalidParameterValueException("Parameter [{$key}] must be a valid date.", 0, $e);
        }
    }

    private function assertUrl(string $key, mixed $value): void
    {
        if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidParameterValueException("Parameter [{$key}] must be a valid URL.");
        }
    }
}

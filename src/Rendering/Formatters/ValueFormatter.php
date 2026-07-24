<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Rendering\Formatters;

use Carbon\Carbon;
use NuzulFikrieCoder\LaravelMailmanager\Enums\ColumnFormat;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\InvalidParameterValueException;
use Throwable;

final class ValueFormatter
{
    /**
     * @param  array<string, mixed>  $column
     */
    public function format(mixed $value, array $column): string
    {
        if ($value === null || $value === '') {
            return (string) ($column['fallback'] ?? '');
        }

        $format = ColumnFormat::tryFrom((string) ($column['format'] ?? 'plain')) ?? ColumnFormat::Plain;

        return match ($format) {
            ColumnFormat::Integer => (string) (int) $value,
            ColumnFormat::Decimal => number_format((float) $value, 2, '.', ''),
            ColumnFormat::Currency => $this->currency($value, (string) ($column['currency'] ?? 'USD')),
            ColumnFormat::Date => $this->date($value, 'Y-m-d'),
            ColumnFormat::Datetime => $this->date($value, 'Y-m-d H:i:s'),
            ColumnFormat::Percentage => number_format((float) $value, 2, '.', '').'%',
            ColumnFormat::Plain => (string) $value,
        };
    }

    private function currency(mixed $value, string $currency): string
    {
        return $currency.' '.number_format((float) $value, 2, '.', ',');
    }

    private function date(mixed $value, string $format): string
    {
        try {
            return Carbon::parse($value)->format($format);
        } catch (Throwable $e) {
            throw new InvalidParameterValueException('Invalid date value for collection column.', 0, $e);
        }
    }
}

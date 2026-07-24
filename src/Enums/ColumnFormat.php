<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum ColumnFormat: string
{
    case Plain = 'plain';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Currency = 'currency';
    case Date = 'date';
    case Datetime = 'datetime';
    case Percentage = 'percentage';
}

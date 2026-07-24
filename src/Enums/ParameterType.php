<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum ParameterType: string
{
    case String = 'string';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';
    case Url = 'url';
    case Collection = 'collection';
}

<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum SettingType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Float = 'float';
    case Json = 'json';
    case Encrypted = 'encrypted';
}

<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum EmptyCollectionBehavior: string
{
    case Hide = 'hide';
    case HeadersMessage = 'headers_message';
    case CustomFallback = 'custom_fallback';
    case Fail = 'fail';
}

<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @deprecated Use {@see EmailTemplate} or {@see MailmanagerSettings} instead.
 * @see \NuzulFikrieCoder\LaravelMailmanager\LaravelMailmanager
 */
class LaravelMailmanager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NuzulFikrieCoder\LaravelMailmanager\LaravelMailmanager::class;
    }
}

<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum TemplateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';

    public function isSendable(): bool
    {
        return $this === self::Active;
    }
}

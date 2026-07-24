<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum EmailLogStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Suppressed = 'suppressed';
}

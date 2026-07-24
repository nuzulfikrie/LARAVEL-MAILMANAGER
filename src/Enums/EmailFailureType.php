<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Enums;

enum EmailFailureType: string
{
    case Validation = 'validation';
    case SmtpConnection = 'smtp_connection';
    case SmtpAuth = 'smtp_auth';
    case ProviderReject = 'provider_reject';
    case Queue = 'queue';
    case Suppressed = 'suppressed';
}

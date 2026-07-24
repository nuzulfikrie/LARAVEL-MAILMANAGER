<?php

declare(strict_types=1);

use NuzulFikrieCoder\LaravelMailmanager\Enums\ColumnFormat;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmptyCollectionBehavior;
use NuzulFikrieCoder\LaravelMailmanager\Enums\ParameterType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\TemplateStatus;

it('exposes template status values and sendable check', function () {
    expect(TemplateStatus::Draft->value)->toBe('draft')
        ->and(TemplateStatus::Active->isSendable())->toBeTrue()
        ->and(TemplateStatus::Draft->isSendable())->toBeFalse()
        ->and(TemplateStatus::Inactive->isSendable())->toBeFalse()
        ->and(TemplateStatus::Archived->isSendable())->toBeFalse();
});

it('exposes supporting enum cases', function () {
    expect(EmailLogStatus::cases())->not->toBeEmpty()
        ->and(EmailFailureType::cases())->not->toBeEmpty()
        ->and(SettingType::cases())->not->toBeEmpty()
        ->and(EmptyCollectionBehavior::cases())->not->toBeEmpty()
        ->and(ParameterType::Collection->value)->toBe('collection')
        ->and(ColumnFormat::Currency->value)->toBe('currency');
});

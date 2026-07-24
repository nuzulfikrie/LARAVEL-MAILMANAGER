<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Facades;

use Illuminate\Support\Facades\Facade;
use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use NuzulFikrieCoder\LaravelMailmanager\Services\SettingsRepository;

/**
 * @method static array<string, mixed> group(string $group)
 * @method static mixed get(string $group, string $key, mixed $default = null)
 * @method static void set(string $group, string $key, mixed $value, SettingType $type = SettingType::String, bool $encrypted = false)
 * @method static void putMany(string $group, array<string, mixed> $values)
 * @method static void forgetCache(?string $group = null)
 * @method static array<string, mixed> groupForDisplay(string $group)
 *
 * @see SettingsRepository
 */
class MailmanagerSettings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsRepository::class;
    }
}

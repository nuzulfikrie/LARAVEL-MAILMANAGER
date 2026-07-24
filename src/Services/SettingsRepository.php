<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use NuzulFikrieCoder\LaravelMailmanager\Models\Setting;

final class SettingsRepository
{
    /**
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        /** @var array<string, mixed> $values */
        $values = $this->cacheStore()->remember(
            $this->cacheKey($group),
            (int) config('laravel-mailmanager.cache.settings_ttl', 3600),
            fn (): array => $this->loadGroup($group),
        );

        return $values;
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $groupValues = $this->group($group);

        return array_key_exists($key, $groupValues) ? $groupValues[$key] : $default;
    }

    public function set(
        string $group,
        string $key,
        mixed $value,
        SettingType $type = SettingType::String,
        bool $encrypted = false,
    ): void {
        $storedValue = $this->serializeValue($value, $type, $encrypted);

        Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value' => $storedValue,
                'type' => $encrypted ? SettingType::Encrypted : $type,
                'is_encrypted' => $encrypted || $type === SettingType::Encrypted,
            ],
        );

        $this->forgetCache($group);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            if ($key === 'password' && ($value === null || $value === '')) {
                continue;
            }

            $encrypted = $key === 'password';
            $type = $this->inferType($key, $value, $encrypted);

            $this->set($group, (string) $key, $value, $type, $encrypted);
        }
    }

    public function forgetCache(?string $group = null): void
    {
        if ($group !== null) {
            $this->cacheStore()->forget($this->cacheKey($group));

            return;
        }

        $groups = Setting::query()->distinct()->pluck('group');

        foreach ($groups as $g) {
            $this->cacheStore()->forget($this->cacheKey((string) $g));
        }
    }

    /**
     * Safe for API/UI: never includes decrypted password.
     *
     * @return array<string, mixed>
     */
    public function groupForDisplay(string $group): array
    {
        $values = $this->group($group);

        if (array_key_exists('password', $values)) {
            $set = $values['password'] !== null && $values['password'] !== '';
            unset($values['password']);
            $values['password_set'] = $set;
            $values['password'] = $set ? '********' : null;
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGroup(string $group): array
    {
        $rows = Setting::query()->group($group)->get();
        $out = [];

        foreach ($rows as $row) {
            $out[$row->key] = $this->castValue($row);
        }

        return $out;
    }

    private function castValue(Setting $row): mixed
    {
        $raw = $row->value;

        if ($row->is_encrypted || $row->type === SettingType::Encrypted) {
            if ($raw === null || $raw === '') {
                return null;
            }

            try {
                return Crypt::decryptString($raw);
            } catch (DecryptException) {
                return null;
            }
        }

        return match ($row->type) {
            SettingType::Integer => $raw === null ? null : (int) $raw,
            SettingType::Boolean => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            SettingType::Float => $raw === null ? null : (float) $raw,
            SettingType::Json => $raw === null ? null : json_decode($raw, true),
            default => $raw,
        };
    }

    private function serializeValue(mixed $value, SettingType $type, bool $encrypted): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = match ($type) {
            SettingType::Boolean => $value ? '1' : '0',
            SettingType::Json => is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
            SettingType::Integer, SettingType::Float => (string) $value,
            default => (string) $value,
        };

        if ($encrypted || $type === SettingType::Encrypted) {
            return Crypt::encryptString($string);
        }

        return $string;
    }

    private function inferType(string $key, mixed $value, bool $encrypted): SettingType
    {
        if ($encrypted) {
            return SettingType::Encrypted;
        }

        return match (true) {
            is_bool($value) => SettingType::Boolean,
            is_int($value) => SettingType::Integer,
            is_float($value) => SettingType::Float,
            is_array($value) => SettingType::Json,
            in_array($key, ['port', 'timeout'], true) => SettingType::Integer,
            in_array($key, ['delivery_enabled'], true) => SettingType::Boolean,
            default => SettingType::String,
        };
    }

    private function cacheKey(string $group): string
    {
        return (string) config('laravel-mailmanager.cache.settings_key', 'laravel-mailmanager.settings').'.'.$group;
    }

    private function cacheStore(): CacheRepository
    {
        $store = config('laravel-mailmanager.cache.settings_store');

        return $store ? Cache::store($store) : Cache::store();
    }
}

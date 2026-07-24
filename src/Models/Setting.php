<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use NuzulFikrieCoder\LaravelMailmanager\Database\Factories\SettingFactory;
use NuzulFikrieCoder\LaravelMailmanager\Enums\SettingType;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property SettingType $type
 * @property bool $is_encrypted
 *
 * @use HasFactory<SettingFactory>
 */
class Setting extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_encrypted',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'value',
    ];

    /**
     * @return list<string>
     */
    public function getAuditInclude(): array
    {
        return [
            'group',
            'key',
            'type',
            'is_encrypted',
        ];
    }

    /**
     * Never audit ciphertext or plaintext password values.
     *
     * @return list<string>
     */
    public function getAuditExclude(): array
    {
        return [
            'value',
        ];
    }

    public function getTable(): string
    {
        return (string) config('laravel-mailmanager.tables.settings', 'mailmanager_settings');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_encrypted' => 'boolean',
        ];
    }

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    /**
     * @param  Builder<Setting>  $query
     * @return Builder<Setting>
     */
    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }
}

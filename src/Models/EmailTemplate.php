<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use NuzulFikrieCoder\LaravelMailmanager\Database\Factories\EmailTemplateFactory;
use NuzulFikrieCoder\LaravelMailmanager\Enums\TemplateStatus;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $subject
 * @property array<string, mixed>|null $design_json
 * @property string $html_content
 * @property array<string, mixed>|null $parameters
 * @property TemplateStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @use HasFactory<EmailTemplateFactory>
 */
class EmailTemplate extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'subject',
        'design_json',
        'html_content',
        'parameters',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * @return list<string>
     */
    public function getAuditInclude(): array
    {
        return [
            'name',
            'slug',
            'description',
            'subject',
            'design_json',
            'html_content',
            'parameters',
            'status',
            'created_by',
            'updated_by',
        ];
    }

    public function getTable(): string
    {
        return (string) config('laravel-mailmanager.tables.templates', 'email_templates');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'design_json' => 'array',
            'parameters' => 'array',
            'status' => TemplateStatus::class,
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function newFactory(): EmailTemplateFactory
    {
        return EmailTemplateFactory::new();
    }

    /**
     * @return HasMany<EmailTemplateVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class);
    }

    /**
     * @return HasOne<EmailTemplateVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(EmailTemplateVersion::class)->latestOfMany('version');
    }

    /**
     * @return HasMany<EmailLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * @param  Builder<EmailTemplate>  $query
     * @return Builder<EmailTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TemplateStatus::Active);
    }

    /**
     * Active and not soft-deleted (soft deletes are applied automatically).
     *
     * @param  Builder<EmailTemplate>  $query
     * @return Builder<EmailTemplate>
     */
    public function scopeSendable(Builder $query): Builder
    {
        return $query->where('status', TemplateStatus::Active);
    }

    /**
     * @param  Builder<EmailTemplate>  $query
     * @return Builder<EmailTemplate>
     */
    public function scopeStatus(Builder $query, TemplateStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function isSendable(): bool
    {
        return $this->status->isSendable() && ! $this->trashed();
    }
}

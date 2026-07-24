<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use NuzulFikrieCoder\LaravelMailmanager\Database\Factories\EmailTemplateVersionFactory;

/**
 * @property int $id
 * @property int $email_template_id
 * @property int $version
 * @property string $content_hash
 * @property string $subject
 * @property array<string, mixed>|null $design_json
 * @property string $html_content
 * @property array<string, mixed>|null $parameters
 * @property int|null $created_by
 */
class EmailTemplateVersion extends Model
{
    /** @use HasFactory<EmailTemplateVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'email_template_id',
        'version',
        'content_hash',
        'subject',
        'design_json',
        'html_content',
        'parameters',
        'created_by',
    ];

    public function getTable(): string
    {
        return (string) config('laravel-mailmanager.tables.template_versions', 'email_template_versions');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'design_json' => 'array',
            'parameters' => 'array',
            'created_by' => 'integer',
        ];
    }

    protected static function newFactory(): EmailTemplateVersionFactory
    {
        return EmailTemplateVersionFactory::new();
    }

    /**
     * @return BelongsTo<EmailTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    /**
     * @return HasMany<EmailLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'email_template_version_id');
    }
}

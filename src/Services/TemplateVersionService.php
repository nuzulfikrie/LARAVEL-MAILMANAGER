<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Services;

use Illuminate\Support\Facades\DB;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplateVersion;
use NuzulFikrieCoder\LaravelMailmanager\Support\ContentHasher;

final class TemplateVersionService
{
    public function ensureCurrentVersion(EmailTemplate $template, ?int $actorId = null): EmailTemplateVersion
    {
        return DB::transaction(function () use ($template, $actorId): EmailTemplateVersion {
            /** @var EmailTemplate $locked */
            $locked = EmailTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();

            $hash = $this->hashTemplate($locked);
            $latest = EmailTemplateVersion::query()
                ->where('email_template_id', $locked->id)
                ->orderByDesc('version')
                ->first();

            if ($latest !== null && $latest->content_hash === $hash) {
                return $latest;
            }

            $next = $latest === null ? 1 : ($latest->version + 1);

            return EmailTemplateVersion::query()->create([
                'email_template_id' => $locked->id,
                'version' => $next,
                'content_hash' => $hash,
                'subject' => $locked->subject,
                'design_json' => $locked->design_json ?? [],
                'html_content' => $locked->html_content,
                'parameters' => $locked->parameters ?? [],
                'created_by' => $actorId,
            ]);
        });
    }

    public function snapshotIfContentChanged(EmailTemplate $template, ?int $actorId = null): ?EmailTemplateVersion
    {
        $hash = $this->hashTemplate($template);
        $latest = EmailTemplateVersion::query()
            ->where('email_template_id', $template->id)
            ->orderByDesc('version')
            ->first();

        if ($latest !== null && $latest->content_hash === $hash) {
            return null;
        }

        return $this->ensureCurrentVersion($template, $actorId);
    }

    public function hashTemplate(EmailTemplate $template): string
    {
        return ContentHasher::hash(
            $template->subject,
            $template->html_content,
            $template->design_json ?? [],
            $template->parameters ?? [],
        );
    }
}

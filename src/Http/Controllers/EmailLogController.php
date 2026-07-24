<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailFailureType;
use NuzulFikrieCoder\LaravelMailmanager\Enums\EmailLogStatus;
use NuzulFikrieCoder\LaravelMailmanager\Mail\RawHtmlMailable;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailLog;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailLogService;
use NuzulFikrieCoder\LaravelMailmanager\Services\MailConfigApplier;
use Throwable;

class EmailLogController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EmailLogService $logs,
        private readonly MailConfigApplier $mailConfig,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmailLog::class);

        $query = EmailLog::query()->latest();

        if ($request->filled('status')) {
            $status = EmailLogStatus::tryFrom((string) $request->string('status'));

            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%'.$request->string('recipient').'%');
        }

        if ($request->filled('template_id')) {
            $query->where('email_template_id', (int) $request->input('template_id'));
        }

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::logs.index';

        return view($viewName, [
            'logs' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function show(EmailLog $log): View
    {
        $this->authorize('view', $log);

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::logs.show';

        return view($viewName, [
            'log' => $log,
        ]);
    }

    public function retry(EmailLog $log): RedirectResponse
    {
        $this->authorize('retry', $log);

        if (! $log->isRetryEligible()) {
            return back()->withErrors([
                'retry' => 'This log is not eligible for retry. Store rendered HTML at send time and ensure the failure is transport-related.',
            ]);
        }

        $this->mailConfig->apply();
        $mailerName = (string) config('laravel-mailmanager.mail.mailer_name', 'mailmanager');

        try {
            Mail::mailer($mailerName)
                ->to($log->recipient)
                ->send(new RawHtmlMailable(
                    emailSubject: $log->rendered_subject,
                    htmlBody: (string) $log->rendered_html,
                ));

            $this->logs->markSent($log);
        } catch (Throwable $e) {
            $this->logs->markFailed(
                $log,
                $e->getMessage(),
                $log->failure_type ?? EmailFailureType::ProviderReject,
            );

            return back()->withErrors(['retry' => $e->getMessage()]);
        }

        return back()->with('status', 'Email retry sent.');
    }
}

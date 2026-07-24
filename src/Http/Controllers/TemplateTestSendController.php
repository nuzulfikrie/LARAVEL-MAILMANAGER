<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use NuzulFikrieCoder\LaravelMailmanager\Http\Requests\SendTestEmailRequest;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;
use Throwable;

class TemplateTestSendController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EmailTemplateService $templates,
    ) {}

    public function __invoke(SendTestEmailRequest $request, EmailTemplate $template): RedirectResponse
    {
        $this->authorize('sendTest', $template);

        try {
            $this->templates->sendTest(
                $template->slug,
                (string) $request->validated('to'),
                $request->parameters(),
            );
        } catch (Throwable $e) {
            return back()->withErrors(['test' => $e->getMessage()]);
        }

        return back()->with('status', 'Test email sent.');
    }
}

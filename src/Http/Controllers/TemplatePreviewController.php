<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use NuzulFikrieCoder\LaravelMailmanager\Http\Requests\PreviewTemplateRequest;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;
use Throwable;

class TemplatePreviewController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EmailTemplateService $templates,
    ) {}

    public function __invoke(PreviewTemplateRequest $request, EmailTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        try {
            $rendered = $this->templates->render($template->slug, $request->parameters(), strict: false);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'subject' => $rendered->subject,
            'html' => $rendered->html,
        ]);
    }
}

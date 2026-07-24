<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use NuzulFikrieCoder\LaravelMailmanager\Exceptions\ProtectedTemplateException;
use NuzulFikrieCoder\LaravelMailmanager\Http\Requests\StoreTemplateRequest;
use NuzulFikrieCoder\LaravelMailmanager\Http\Requests\UpdateTemplateRequest;
use NuzulFikrieCoder\LaravelMailmanager\Models\EmailTemplate;
use NuzulFikrieCoder\LaravelMailmanager\Services\EmailTemplateService;

class TemplateController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly EmailTemplateService $templates,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $items = EmailTemplate::query()->latest()->paginate(20);

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::templates.index';

        return view($viewName, [
            'templates' => $items,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EmailTemplate::class);

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::templates.create';

        return view($viewName);
    }

    public function store(StoreTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $template = $this->templates->create(
            $request->templateData(),
            $this->actorId($request),
        );

        return redirect()
            ->route(config('laravel-mailmanager.route.name').'templates.edit', $template)
            ->with('status', 'Template created.');
    }

    public function edit(EmailTemplate $template): View
    {
        $this->authorize('update', $template);

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::templates.edit';

        return view($viewName, [
            'template' => $template,
        ]);
    }

    public function update(UpdateTemplateRequest $request, EmailTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        $this->templates->update($template, $request->templateData(), $this->actorId($request));

        return redirect()
            ->route(config('laravel-mailmanager.route.name').'templates.edit', $template)
            ->with('status', 'Template saved.');
    }

    public function destroy(EmailTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        try {
            $this->templates->delete($template);
        } catch (ProtectedTemplateException $e) {
            return back()->withErrors(['template' => $e->getMessage()]);
        }

        return redirect()
            ->route(config('laravel-mailmanager.route.name').'templates.index')
            ->with('status', 'Template deleted.');
    }

    public function versions(EmailTemplate $template): View
    {
        $this->authorize('view', $template);

        /** @var view-string $viewName */
        $viewName = 'laravel-mailmanager::templates.versions';

        return view($viewName, [
            'template' => $template,
            'versions' => $template->versions()->orderByDesc('version')->get(),
            'audits' => $template->audits()->latest()->limit(50)->get(),
        ]);
    }

    public function duplicate(EmailTemplate $template): RedirectResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $copy = $this->templates->duplicate($template);

        return redirect()
            ->route(config('laravel-mailmanager.route.name').'templates.edit', $copy)
            ->with('status', 'Template duplicated.');
    }

    public function activate(EmailTemplate $template): RedirectResponse
    {
        $this->authorize('activate', $template);

        $this->templates->activate($template, $this->actorId());

        return back()->with('status', 'Template activated.');
    }

    public function deactivate(EmailTemplate $template): RedirectResponse
    {
        $this->authorize('activate', $template);

        $this->templates->deactivate($template, $this->actorId());

        return back()->with('status', 'Template deactivated.');
    }

    private function actorId(?object $request = null): ?int
    {
        $user = $request !== null && method_exists($request, 'user')
            ? $request->user()
            : auth()->user();

        if ($user === null) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        return is_numeric($id) ? (int) $id : null;
    }
}

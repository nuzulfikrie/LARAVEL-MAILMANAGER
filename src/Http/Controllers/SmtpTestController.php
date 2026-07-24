<?php

declare(strict_types=1);

namespace NuzulFikrieCoder\LaravelMailmanager\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use NuzulFikrieCoder\LaravelMailmanager\Services\SmtpProbeService;
use Throwable;

class SmtpTestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly SmtpProbeService $probe,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        Gate::authorize((string) config('laravel-mailmanager.permissions.settings.update'));

        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->probe->sendTest((string) $data['email']);
        } catch (Throwable $e) {
            return back()->withErrors(['smtp' => $e->getMessage()]);
        }

        return back()->with('status', 'SMTP probe sent.');
    }
}

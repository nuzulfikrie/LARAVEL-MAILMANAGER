<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('laravel-mailmanager.ui.brand', 'Mailmanager'))</title>
    <link rel="stylesheet" href="{{ asset('vendor/laravel-mailmanager/css/mailmanager.css') }}">
    @stack('head')
</head>
<body class="mm-body">
<div class="mm-shell">
    <nav class="mm-nav">
        <a class="mm-brand" href="{{ route(config('laravel-mailmanager.route.name').'templates.index') }}">
            {{ config('laravel-mailmanager.ui.brand', 'Mailmanager') }}
        </a>
        <a href="{{ route(config('laravel-mailmanager.route.name').'templates.index') }}">Templates</a>
        <a href="{{ route(config('laravel-mailmanager.route.name').'settings.mail.edit') }}">SMTP Settings</a>
        <a href="{{ route(config('laravel-mailmanager.route.name').'logs.index') }}">Email Logs</a>
    </nav>

    <main class="mm-main">
        @if (session('status'))
            <div class="mm-alert mm-alert-ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mm-alert mm-alert-err">
                <ul style="margin:0;padding-left:1.1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>

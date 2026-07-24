@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'Log #'.$log->id)

@section('content')
    <div class="mm-row mm-space" style="margin-bottom:1rem;">
        <h1 class="mm-h1">Log #{{ $log->id }}</h1>
        <a class="mm-btn" href="{{ route(config('laravel-mailmanager.route.name').'logs.index') }}">Back</a>
    </div>

    <div class="mm-card">
        <p><strong>Recipient:</strong> {{ $log->recipient }}</p>
        <p><strong>Subject:</strong> {{ $log->rendered_subject }}</p>
        <p><strong>Status:</strong> {{ $log->status->value }}</p>
        <p><strong>Failure type:</strong> {{ $log->failure_type?->value ?? '—' }}</p>
        <p><strong>Failure reason:</strong> {{ $log->failure_reason ?? '—' }}</p>
        <p><strong>Template ID:</strong> {{ $log->email_template_id ?? '—' }}</p>
        <p><strong>Version ID:</strong> {{ $log->email_template_version_id ?? '—' }}</p>
        <p><strong>Test:</strong> {{ $log->is_test ? 'yes' : 'no' }}</p>
        <p><strong>Meta:</strong></p>
        <pre class="mm-muted" style="white-space:pre-wrap;">{{ json_encode($log->meta, JSON_PRETTY_PRINT) }}</pre>

        @if ($log->isRetryEligible())
            <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'logs.retry', $log) }}" style="margin-top:1rem;">
                @csrf
                <button class="mm-btn mm-btn-primary" type="submit">Retry send</button>
            </form>
        @else
            <p class="mm-muted" style="margin-top:1rem;">Retry unavailable (requires stored rendered HTML and a transport-class failure).</p>
        @endif
    </div>
@endsection

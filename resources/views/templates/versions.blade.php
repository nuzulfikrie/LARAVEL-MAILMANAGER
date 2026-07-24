@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'Versions · '.$template->name)

@section('content')
    <div class="mm-row mm-space" style="margin-bottom:1rem;">
        <h1 class="mm-h1">History · {{ $template->name }}</h1>
        <a class="mm-btn" href="{{ route(config('laravel-mailmanager.route.name').'templates.edit', $template) }}">Back to edit</a>
    </div>

    <div class="mm-card" style="margin-bottom:1rem;">
        <h2 class="mm-h2">Content versions</h2>
        <table class="mm-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Hash</th>
                <th>Subject</th>
                <th>Created</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($versions as $version)
                <tr>
                    <td>{{ $version->version }}</td>
                    <td><code>{{ \Illuminate\Support\Str::limit($version->content_hash, 16) }}</code></td>
                    <td>{{ $version->subject }}</td>
                    <td class="mm-muted">{{ optional($version->created_at)->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="mm-muted">No versions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mm-card">
        <h2 class="mm-h2">Audit trail (OwenIt)</h2>
        <table class="mm-table">
            <thead>
            <tr>
                <th>Event</th>
                <th>User</th>
                <th>Changes</th>
                <th>When</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($audits as $audit)
                <tr>
                    <td>{{ $audit->event }}</td>
                    <td class="mm-muted">{{ $audit->user_id ?? '—' }}</td>
                    <td>
                        <pre class="mm-muted" style="white-space:pre-wrap;margin:0;font-size:.75rem;">{{ json_encode(['old' => $audit->old_values, 'new' => $audit->new_values], JSON_PRETTY_PRINT) }}</pre>
                    </td>
                    <td class="mm-muted">{{ optional($audit->created_at)->toDateTimeString() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="mm-muted">No audit rows yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection

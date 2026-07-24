@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'Email logs')

@section('content')
    <h1 class="mm-h1">Email logs</h1>

    <div class="mm-card" style="margin-bottom:1rem;">
        <form method="get" class="mm-row">
            <div class="mm-field" style="margin:0;">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Any</option>
                    @foreach (['queued','sent','failed','suppressed'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mm-field" style="margin:0;">
                <label for="recipient">Recipient</label>
                <input id="recipient" name="recipient" value="{{ request('recipient') }}">
            </div>
            <div class="mm-field" style="margin:0;">
                <label for="template_id">Template ID</label>
                <input id="template_id" name="template_id" value="{{ request('template_id') }}">
            </div>
            <button class="mm-btn" type="submit" style="align-self:end;">Filter</button>
        </form>
    </div>

    <div class="mm-card">
        <table class="mm-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Recipient</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Test</th>
                <th>When</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->recipient }}</td>
                    <td>{{ $log->rendered_subject }}</td>
                    <td><span class="mm-badge {{ $log->status->value === 'failed' ? 'failed' : ($log->status->value === 'sent' ? 'active' : '') }}">{{ $log->status->value }}</span></td>
                    <td>{{ $log->is_test ? 'yes' : 'no' }}</td>
                    <td class="mm-muted">{{ optional($log->created_at)->diffForHumans() }}</td>
                    <td><a class="mm-btn" href="{{ route(config('laravel-mailmanager.route.name').'logs.show', $log) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="mm-muted">No logs yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div style="margin-top:1rem;">{{ $logs->links() }}</div>
    </div>
@endsection

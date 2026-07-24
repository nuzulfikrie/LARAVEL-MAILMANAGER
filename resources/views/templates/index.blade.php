@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'Templates')

@section('content')
    <div class="mm-row mm-space" style="margin-bottom:1rem;">
        <h1 class="mm-h1">Email templates</h1>
        <a class="mm-btn mm-btn-primary" href="{{ route(config('laravel-mailmanager.route.name').'templates.create') }}">New template</a>
    </div>

    <div class="mm-card">
        <table class="mm-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Updated</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td><code>{{ $template->slug }}</code></td>
                    <td>
                        <span class="mm-badge {{ $template->status->value === 'active' ? 'active' : '' }}">
                            {{ $template->status->value }}
                        </span>
                    </td>
                    <td class="mm-muted">{{ optional($template->updated_at)->diffForHumans() }}</td>
                    <td class="mm-row">
                        <a class="mm-btn" href="{{ route(config('laravel-mailmanager.route.name').'templates.edit', $template) }}">Edit</a>
                        <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'templates.duplicate', $template) }}">
                            @csrf
                            <button class="mm-btn" type="submit">Duplicate</button>
                        </form>
                        @if ($template->status->value === 'active')
                            <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'templates.deactivate', $template) }}">
                                @csrf
                                <button class="mm-btn" type="submit">Deactivate</button>
                            </form>
                        @else
                            <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'templates.activate', $template) }}">
                                @csrf
                                <button class="mm-btn" type="submit">Activate</button>
                            </form>
                        @endif
                        <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'templates.destroy', $template) }}" onsubmit="return confirm('Delete this template?')">
                            @csrf
                            @method('DELETE')
                            <button class="mm-btn mm-btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="mm-muted">No templates yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div style="margin-top:1rem;">{{ $templates->links() }}</div>
    </div>
@endsection

@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'Edit '.$template->name)

@section('content')
    <div class="mm-row mm-space" style="margin-bottom:1rem;">
        <h1 class="mm-h1">Edit {{ $template->name }}</h1>
        <div class="mm-row">
            <span class="mm-badge {{ $template->status->value === 'active' ? 'active' : '' }}">{{ $template->status->value }}</span>
            <a class="mm-btn" href="{{ route(config('laravel-mailmanager.route.name').'templates.versions', $template) }}">Versions &amp; audit</a>
        </div>
    </div>

    <div class="mm-card" style="margin-bottom:1rem;">
        @include('laravel-mailmanager::templates._form', [
            'action' => route(config('laravel-mailmanager.route.name').'templates.update', $template),
            'method' => 'PUT',
            'template' => $template,
        ])
    </div>

    <div class="mm-grid-2">
        <div class="mm-card">
            <h2 class="mm-h2">Preview</h2>
            <p class="mm-muted">Sample parameters JSON (optional)</p>
            <div class="mm-field">
                <textarea id="preview_parameters" placeholder='{"name":"Ali"}'>{}</textarea>
            </div>
            <button class="mm-btn" type="button" id="mm-preview-btn">Render preview</button>
            <iframe class="mm-preview-frame" id="mm-preview-frame" title="Preview" style="margin-top:1rem;"></iframe>
        </div>

        <div class="mm-card">
            <h2 class="mm-h2">Send test email</h2>
            <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'templates.send-test', $template) }}">
                @csrf
                <div class="mm-field">
                    <label for="test_to">Recipient</label>
                    <input id="test_to" name="to" type="email" required value="{{ old('to') }}">
                </div>
                <div class="mm-field">
                    <label for="test_parameters">Parameters JSON</label>
                    <textarea id="test_parameters" name="parameters">{{ old('parameters', '{"name":"Ali"}') }}</textarea>
                </div>
                <button class="mm-btn mm-btn-primary" type="submit">Send test</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('mm-preview-btn')?.addEventListener('click', async function () {
    const params = document.getElementById('preview_parameters')?.value || '{}';
    const res = await fetch(@json(route(config('laravel-mailmanager.route.name').'templates.preview', $template)), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': @json(csrf_token()),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ parameters: params }),
    });
    const data = await res.json();
    const frame = document.getElementById('mm-preview-frame');
    if (!res.ok) {
        frame.srcdoc = '<p style="color:red;padding:1rem;">' + (data.message || 'Preview failed') + '</p>';
        return;
    }
    frame.srcdoc = data.html || '';
});
</script>
@endpush

@php
    $routeName = config('laravel-mailmanager.route.name');
    $projectId = config('laravel-mailmanager.unlayer.project_id');
    $designJson = old('design_json', $template?->design_json ? json_encode($template->design_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '{}');
    $parametersJson = old('parameters', $template?->parameters ? json_encode($template->parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '{}');
@endphp

<form id="mailmanager-template-form" method="post" action="{{ $action }}">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <div class="mm-grid-2">
        <div class="mm-field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $template->name ?? '') }}">
        </div>
        <div class="mm-field">
            <label for="slug">Slug</label>
            <input id="slug" name="slug" type="text" value="{{ old('slug', $template->slug ?? '') }}" placeholder="auto from name">
        </div>
    </div>

    <div class="mm-field">
        <label for="description">Description</label>
        <textarea id="description" name="description">{{ old('description', $template->description ?? '') }}</textarea>
    </div>

    <div class="mm-field">
        <label for="subject">Subject</label>
        <div class="mm-row" style="margin-bottom:.35rem;">
            <button class="mm-btn" type="button" onclick="MailmanagerParameters.insert('subject','{name}')">Insert {name}</button>
        </div>
        <input id="subject" name="subject" type="text" required value="{{ old('subject', $template->subject ?? '') }}">
    </div>

    <div class="mm-field">
        <label for="parameters">Parameter schema (JSON)</label>
        <textarea id="parameters" name="parameters">{{ $parametersJson }}</textarea>
    </div>

    <div class="mm-field">
        <label>Design</label>
        @if ($projectId)
            <div id="mailmanager-unlayer" class="mm-editor"></div>
            <input type="hidden" id="design_json" name="design_json" value="{{ $designJson }}">
            <textarea id="html_content" name="html_content" style="display:none;">{{ old('html_content', $template->html_content ?? '<p>Hello {name}</p>') }}</textarea>
        @else
            <p class="mm-muted">Set MAILMANAGER_UNLAYER_PROJECT_ID to enable the Unlayer editor. Editing HTML/JSON directly for now.</p>
            <label for="design_json">Design JSON</label>
            <textarea id="design_json" name="design_json">{{ $designJson }}</textarea>
            <label for="html_content">HTML content</label>
            <textarea id="html_content" name="html_content" required>{{ old('html_content', $template->html_content ?? '<p>Hello {name}</p>') }}</textarea>
        @endif
    </div>

    <button class="mm-btn mm-btn-primary" type="submit">Save template</button>
</form>

@push('scripts')
<script src="{{ asset('vendor/laravel-mailmanager/js/parameter-insert.js') }}"></script>
@if ($projectId)
    <script src="{{ config('laravel-mailmanager.unlayer.cdn') }}"></script>
    <script src="{{ asset('vendor/laravel-mailmanager/js/unlayer-bridge.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            MailmanagerUnlayer.init({
                projectId: @json($projectId),
                displayMode: @json(config('laravel-mailmanager.unlayer.display_mode')),
                locale: @json(config('laravel-mailmanager.unlayer.locale')),
                designJson: @json($designJson),
                formId: 'mailmanager-template-form',
            });
        });
    </script>
@endif
@endpush

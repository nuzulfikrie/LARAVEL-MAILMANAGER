@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'Create template')

@section('content')
    <h1 class="mm-h1">Create template</h1>
    <div class="mm-card">
        @include('laravel-mailmanager::templates._form', [
            'action' => route(config('laravel-mailmanager.route.name').'templates.store'),
            'method' => 'POST',
            'template' => null,
        ])
    </div>
@endsection

@extends(config('laravel-mailmanager.ui.layout'))

@section('title', 'SMTP Settings')

@section('content')
    <h1 class="mm-h1">SMTP settings</h1>

    <div class="mm-card" style="margin-bottom:1rem;">
        <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'settings.mail.update') }}">
            @csrf
            @method('PUT')

            <div class="mm-grid-2">
                <div class="mm-field">
                    <label for="mailer">Mailer transport</label>
                    <input id="mailer" name="mailer" value="{{ old('mailer', $settings['mailer'] ?? 'smtp') }}">
                </div>
                <div class="mm-field">
                    <label for="encryption">Encryption</label>
                    <select id="encryption" name="encryption">
                        @foreach (['none','tls','ssl'] as $enc)
                            <option value="{{ $enc }}" @selected(old('encryption', $settings['encryption'] ?? 'tls') === $enc)>{{ strtoupper($enc) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mm-field">
                    <label for="host">Host</label>
                    <input id="host" name="host" value="{{ old('host', $settings['host'] ?? '') }}">
                </div>
                <div class="mm-field">
                    <label for="port">Port</label>
                    <input id="port" name="port" type="number" value="{{ old('port', $settings['port'] ?? 587) }}">
                </div>
                <div class="mm-field">
                    <label for="username">Username</label>
                    <input id="username" name="username" value="{{ old('username', $settings['username'] ?? '') }}">
                </div>
                <div class="mm-field">
                    <label for="password">Password {{ !empty($settings['password_set']) ? '(set — leave blank to keep)' : '' }}</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" placeholder="{{ !empty($settings['password_set']) ? '********' : '' }}">
                </div>
                <div class="mm-field">
                    <label for="from_address">From address</label>
                    <input id="from_address" name="from_address" type="email" value="{{ old('from_address', $settings['from_address'] ?? '') }}">
                </div>
                <div class="mm-field">
                    <label for="from_name">From name</label>
                    <input id="from_name" name="from_name" value="{{ old('from_name', $settings['from_name'] ?? '') }}">
                </div>
                <div class="mm-field">
                    <label for="reply_to">Reply-To</label>
                    <input id="reply_to" name="reply_to" type="email" value="{{ old('reply_to', $settings['reply_to'] ?? '') }}">
                </div>
                <div class="mm-field">
                    <label for="timeout">Timeout</label>
                    <input id="timeout" name="timeout" type="number" value="{{ old('timeout', $settings['timeout'] ?? 30) }}">
                </div>
                <div class="mm-field">
                    <label for="redirect_to">Redirect all mail to</label>
                    <input id="redirect_to" name="redirect_to" type="email" value="{{ old('redirect_to', $settings['redirect_to'] ?? '') }}">
                </div>
                <div class="mm-field">
                    <label>
                        <input type="checkbox" name="delivery_enabled" value="1" @checked(old('delivery_enabled', $settings['delivery_enabled'] ?? true))>
                        Delivery enabled
                    </label>
                </div>
            </div>

            <button class="mm-btn mm-btn-primary" type="submit">Save settings</button>
        </form>
    </div>

    <div class="mm-card">
        <h2 class="mm-h2">Test SMTP connection</h2>
        <form method="post" action="{{ route(config('laravel-mailmanager.route.name').'settings.mail.test') }}">
            @csrf
            <div class="mm-field">
                <label for="email">Recipient</label>
                <input id="email" name="email" type="email" required>
            </div>
            <button class="mm-btn" type="submit">Send probe</button>
        </form>
    </div>
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f5f4">
    <title>{{ __('app.sign_in_account') }} | {{ config('app.name', 'Records Management System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <header class="agency-header">
            <div class="agency-mark" aria-hidden="true">{{ strtoupper(substr(config('app.name', 'R'), 0, 1)) }}</div>
            <div>
                <p class="agency-kicker">{{ __('app.official_system') }}</p>
                <p class="agency-name">{{ config('app.name', 'Records Management System') }}</p>
            </div>
            <form class="locale-form" method="POST" action="{{ route('locale.update') }}">
                @csrf
                <label class="visually-hidden" for="login-locale">{{ __('app.language') }}</label>
                <select id="login-locale" name="locale" aria-label="{{ __('app.language') }}">
                    <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                    <option value="ar" @selected(app()->getLocale() === 'ar')>العربية</option>
                </select>
                <button type="submit">{{ __('app.change_language') }}</button>
            </form>
        </header>

        <section class="login-panel" aria-labelledby="login-heading">
            <div class="panel-heading">
                <p class="section-label">{{ __('app.secure_access') }}</p>
                <h1 id="login-heading">{{ __('app.sign_in_account') }}</h1>
                <p class="panel-intro">{{ __('app.login_intro') }}</p>
            </div>

            @if ($errors->any())
                <div class="form-alert" role="alert" aria-live="polite">
                    <span class="alert-mark" aria-hidden="true">!</span>
                    <div>
                        <p class="alert-title">{{ __('app.login_unsuccessful') }}</p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="login-form">
                @csrf
                <div class="form-field">
                    <label for="email">{{ __('app.email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        inputmode="email"
                        required
                        autofocus
                        aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                    >
                    @error('email')
                        <p class="field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="password">{{ __('app.password') }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                    >
                    @error('password')
                        <p class="field-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="remember-option" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                    <span>{{ __('app.remember_me') }}</span>
                </label>

                <button class="primary-button" type="submit">
                    <span>{{ __('app.sign_in') }}</span>
                    <span aria-hidden="true">&#8594;</span>
                </button>
            </form>

            <p class="security-note"><span aria-hidden="true">&#9679;</span> {{ __('app.authorized_personnel') }}</p>
        </section>

        <footer class="page-footer">{{ __('app.for_official_use') }} <span aria-hidden="true">&bull;</span> {{ __('app.secure_access_portal') }}</footer>
    </main>
</body>
</html>
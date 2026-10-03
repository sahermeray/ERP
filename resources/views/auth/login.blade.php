<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f5f4">
    <title>Sign in | {{ config('app.name', 'Records Management System') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <header class="agency-header">
            <div class="agency-mark" aria-hidden="true">{{ strtoupper(substr(config('app.name', 'R'), 0, 1)) }}</div>
            <div>
                <p class="agency-kicker">Official system</p>
                <p class="agency-name">{{ config('app.name', 'Records Management System') }}</p>
            </div>
        </header>

        <section class="login-panel" aria-labelledby="login-heading">
            <div class="panel-heading">
                <p class="section-label">Secure access</p>
                <h1 id="login-heading">Sign in to your account</h1>
                <p class="panel-intro">Use your registered government account to continue.</p>
            </div>

            @if ($errors->any())
                <div class="form-alert" role="alert" aria-live="polite">
                    <span class="alert-mark" aria-hidden="true">!</span>
                    <div>
                        <p class="alert-title">Sign-in unsuccessful</p>
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
                    <label for="email">Email address</label>
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
                    <label for="password">Password</label>
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
                    <span>Keep me signed in</span>
                </label>

                <button class="primary-button" type="submit">
                    <span>Sign in</span>
                    <span aria-hidden="true">&#8594;</span>
                </button>
            </form>

            <p class="security-note"><span aria-hidden="true">&#9679;</span> Authorized personnel only</p>
        </section>

        <footer class="page-footer">For official use only <span aria-hidden="true">&bull;</span> Secure access portal</footer>
    </main>
</body>
</html>
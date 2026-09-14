<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install {{ config('app.name', 'Rcentz Fintech Lab') }}</title>
    <style>
        :root { color-scheme: dark; --bg:#0a0b0d; --panel:#111318; --line:#262a33; --muted:#9298a5; --text:#f6f7f9; --accent:#e53935; --ok:#33c27f; --bad:#ff6b6b; }
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; background:radial-gradient(circle at top, #17191f 0, var(--bg) 42%); color:var(--text); font:14px/1.5 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .shell { width:min(1120px, calc(100% - 32px)); margin:0 auto; padding:48px 0 72px; }
        .eyebrow { color:var(--muted); text-transform:uppercase; letter-spacing:.14em; font-size:11px; }
        h1 { font-size:clamp(30px,5vw,54px); line-height:1; margin:10px 0 12px; letter-spacing:-.04em; }
        .lead { max-width:720px; color:#b6bbc5; font-size:16px; }
        .grid { display:grid; grid-template-columns:340px 1fr; gap:20px; margin-top:34px; align-items:start; }
        .card { background:rgba(17,19,24,.92); border:1px solid var(--line); border-radius:18px; padding:22px; box-shadow:0 20px 60px rgba(0,0,0,.22); }
        .card h2 { margin:0 0 14px; font-size:18px; }
        .req { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:11px 0; border-bottom:1px solid #1e222a; }
        .req:last-child { border-bottom:0; }
        .badge { font-size:11px; padding:4px 8px; border-radius:999px; font-weight:700; }
        .badge.ok { color:#bff5da; background:rgba(51,194,127,.13); }
        .badge.bad { color:#ffd0d0; background:rgba(255,107,107,.13); }
        .section { margin-bottom:26px; }
        .section:last-child { margin-bottom:0; }
        .section-title { display:flex; align-items:center; gap:10px; margin-bottom:14px; font-weight:700; }
        .step { width:26px; height:26px; display:grid; place-items:center; border-radius:8px; background:#20242c; color:#d6d9df; font-size:12px; }
        .fields { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .full { grid-column:1 / -1; }
        label { display:block; color:#c9cdd5; font-size:12px; margin-bottom:6px; }
        input, select { width:100%; border:1px solid #303540; background:#0d0f13; color:var(--text); border-radius:11px; padding:11px 12px; outline:none; }
        input:focus, select:focus { border-color:#666e7d; box-shadow:0 0 0 3px rgba(255,255,255,.04); }
        .check { display:flex; gap:10px; align-items:flex-start; padding:13px; border:1px solid var(--line); border-radius:12px; background:#0e1014; }
        .check input { width:auto; margin-top:4px; }
        .check strong { display:block; }
        .check span { color:var(--muted); font-size:12px; }
        button { width:100%; border:0; border-radius:12px; padding:13px 16px; background:var(--accent); color:white; font-weight:800; cursor:pointer; font-size:14px; }
        button:disabled { opacity:.45; cursor:not-allowed; }
        .error { margin-bottom:18px; padding:12px 14px; border:1px solid rgba(255,107,107,.3); background:rgba(255,107,107,.08); color:#ffd1d1; border-radius:12px; }
        .hint { color:var(--muted); font-size:12px; margin-top:8px; }
        @media (max-width:860px) { .grid { grid-template-columns:1fr; } .fields { grid-template-columns:1fr; } .full { grid-column:auto; } .shell { padding-top:28px; } }
    </style>
</head>
<body>
<div class="shell">
    <div class="eyebrow">Rcentz Fintech Lab · Laravel reference build</div>
    <h1>Install the application.</h1>
    <p class="lead">Configure the application, connect an empty MySQL database, create the first administrator and optionally install sample portfolio data.</p>

    <div class="grid">
        <aside class="card">
            <h2>Server readiness</h2>
            @foreach ($requirements as $requirement)
                <div class="req">
                    <span>{{ $requirement['label'] }}</span>
                    <span class="badge {{ $requirement['ok'] ? 'ok' : 'bad' }}">{{ $requirement['ok'] ? 'Ready' : 'Missing' }}</span>
                </div>
            @endforeach
            <p class="hint">The installer locks itself after a successful setup. Remove <code>storage/app/installed</code> only when intentionally rebuilding a test environment.</p>
        </aside>

        <main class="card">
            @if ($errors->any())
                <div class="error">
                    <strong>Installation could not continue.</strong>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('install.store') }}">
                @csrf

                <div class="section">
                    <div class="section-title"><span class="step">1</span> Application</div>
                    <div class="fields">
                        <div>
                            <label for="app_name">Application name</label>
                            <input id="app_name" name="app_name" value="{{ old('app_name', 'Rcentz Fintech Lab') }}" required>
                        </div>
                        <div>
                            <label for="app_env">Environment</label>
                            <select id="app_env" name="app_env">
                                <option value="local" @selected(old('app_env', app()->environment('local') ? 'local' : 'production') === 'local')>Local / development</option>
                                <option value="production" @selected(old('app_env') === 'production')>Production</option>
                            </select>
                        </div>
                        <div class="full">
                            <label for="app_url">Application URL</label>
                            <input id="app_url" type="url" name="app_url" value="{{ old('app_url', request()->getSchemeAndHttpHost()) }}" required>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">2</span> Database</div>
                    <div class="fields">
                        <div>
                            <label for="db_host">Host</label>
                            <input id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>
                        </div>
                        <div>
                            <label for="db_port">Port</label>
                            <input id="db_port" type="number" name="db_port" value="{{ old('db_port', '3306') }}" required>
                        </div>
                        <div>
                            <label for="db_database">Database name</label>
                            <input id="db_database" name="db_database" value="{{ old('db_database') }}" placeholder="rcentz_fintech" required>
                        </div>
                        <div>
                            <label for="db_username">Username</label>
                            <input id="db_username" name="db_username" value="{{ old('db_username', 'root') }}" required>
                        </div>
                        <div class="full">
                            <label for="db_password">Database password</label>
                            <input id="db_password" type="password" name="db_password" autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">3</span> Administrator</div>
                    <div class="fields">
                        <div>
                            <label for="admin_name">Name</label>
                            <input id="admin_name" name="admin_name" value="{{ old('admin_name', 'Platform Administrator') }}" required>
                        </div>
                        <div>
                            <label for="admin_email">Email</label>
                            <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
                        </div>
                        <div>
                            <label for="admin_password">Password</label>
                            <input id="admin_password" type="password" name="admin_password" minlength="10" autocomplete="new-password" required>
                        </div>
                        <div>
                            <label for="admin_password_confirmation">Confirm password</label>
                            <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" minlength="10" autocomplete="new-password" required>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">4</span> Demo world</div>
                    <label class="check">
                        <input type="checkbox" name="seed_demo" value="1" @checked(old('seed_demo', true))>
                        <span>
                            <strong>Install sample portfolio data</strong>
                            <span>Populates sample users, wallets, holdings and activity so the product can be explored immediately.</span>
                        </span>
                    </label>
                </div>

                <button type="submit" @disabled(! $allRequirementsMet)>Install application</button>
            </form>
        </main>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install {{ config('app.name', 'Financial Platform') }}</title>
    <style>
        :root{color-scheme:dark;--bg:#080a0f;--panel:#0f131b;--soft:#151b25;--line:#242c3a;--muted:#8f98a8;--text:#f7f9fc;--accent:#c8102e;--secondary:#7c3aed;--ok:#35c88a;--bad:#ff6b72}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at top right,#182032 0,transparent 34%),linear-gradient(180deg,#0a0d13 0,var(--bg) 100%);color:var(--text);font:14px/1.5 Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.shell{width:min(1220px,calc(100% - 32px));margin:0 auto;padding:40px 0 72px}.eyebrow{color:#aab2c0;text-transform:uppercase;letter-spacing:.16em;font-size:10px;font-weight:700}h1{max-width:860px;font-size:clamp(34px,5vw,58px);line-height:1.02;margin:10px 0 14px;letter-spacing:-.045em}.lead{max-width:790px;color:#b2bac8;font-size:16px}.grid{display:grid;grid-template-columns:330px 1fr;gap:20px;margin-top:32px;align-items:start}.card{background:rgba(15,19,27,.95);border:1px solid var(--line);border-radius:20px;padding:22px;box-shadow:0 24px 70px rgba(0,0,0,.28)}.card h2{margin:0 0 14px;font-size:17px}.req{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid #1d2430}.req:last-child{border-bottom:0}.badge{font-size:10px;padding:4px 8px;border-radius:999px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}.badge.ok{color:#bff6dd;background:rgba(53,200,138,.13)}.badge.bad{color:#ffd3d5;background:rgba(255,107,114,.13)}.section{margin-bottom:30px}.section:last-child{margin-bottom:0}.section-title{display:flex;align-items:center;gap:10px;margin-bottom:14px;font-weight:750}.step{width:27px;height:27px;display:grid;place-items:center;border-radius:9px;background:#202735;color:#e0e5ed;font-size:11px}.fields{display:grid;grid-template-columns:1fr 1fr;gap:14px}.full{grid-column:1/-1}label{display:block;color:#c9cfda;font-size:11px;margin-bottom:6px;font-weight:650}input,select,textarea{width:100%;border:1px solid #30394a;background:#0b0f16;color:var(--text);border-radius:11px;padding:11px 12px;outline:none;font:inherit}textarea{min-height:92px;resize:vertical}input:focus,select:focus,textarea:focus{border-color:#657086;box-shadow:0 0 0 3px rgba(255,255,255,.04)}button{border:0;border-radius:12px;padding:13px 16px;background:var(--accent);color:white;font-weight:800;cursor:pointer;font-size:14px}button:disabled{opacity:.45;cursor:not-allowed}.submit{width:100%}.error{margin-bottom:18px;padding:12px 14px;border:1px solid rgba(255,107,114,.3);background:rgba(255,107,114,.08);color:#ffd7d8;border-radius:12px}.hint{color:var(--muted);font-size:11px;margin-top:7px}.note{margin-top:16px;border:1px solid var(--line);border-radius:14px;background:var(--soft);padding:14px;color:#aeb7c5;font-size:11px}.colors{display:grid;grid-template-columns:1fr 1fr;gap:12px}.color-field{display:flex;align-items:center;gap:10px;border:1px solid #30394a;background:#0b0f16;border-radius:12px;padding:10px}.color-field input[type=color]{width:46px;height:38px;padding:2px;cursor:pointer}.preview{border:1px solid #293244;border-radius:16px;overflow:hidden;background:#0b0f16}.preview-top{padding:14px 16px;background:linear-gradient(135deg,color-mix(in srgb,var(--accent) 22%,#0b0f16),color-mix(in srgb,var(--secondary) 18%,#0b0f16));border-bottom:1px solid #293244}.preview-body{padding:16px}.preview-btn{display:inline-flex;padding:9px 13px;border-radius:10px;background:var(--accent);font-size:11px;font-weight:800}.language-major{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.lang-check{display:flex;align-items:center;gap:8px;border:1px solid #30394a;border-radius:10px;padding:9px 10px;background:#0b0f16}.lang-check input{width:auto}.advanced{margin-top:10px;border:1px solid #30394a;border-radius:12px;padding:12px}.advanced summary{cursor:pointer;font-size:11px;font-weight:700;color:#c9cfda}.minor-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:7px;margin-top:12px;max-height:250px;overflow:auto;padding-right:4px}.minor-grid .lang-check{font-size:10px;padding:7px 8px}@media(max-width:900px){.grid{grid-template-columns:1fr}.fields,.colors{grid-template-columns:1fr}.full{grid-column:auto}.minor-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.shell{padding-top:26px}}@media(max-width:560px){.language-major,.minor-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="shell">
    <div class="eyebrow">Self-hosted platform installation</div>
    <h1>Configure the company, brand, region and operating environment.</h1>
    <p class="lead">The installer creates the database, first administrator, white-label identity and localization authority. After completion the installer locks automatically.</p>

    <div class="grid">
        <aside class="card">
            <h2>Server readiness</h2>
            @foreach($requirements as $requirement)
                <div class="req"><span>{{ $requirement['label'] }}</span><span class="badge {{ $requirement['ok']?'ok':'bad' }}">{{ $requirement['ok']?'Ready':'Missing' }}</span></div>
            @endforeach
            <div class="note">Major languages are enabled by default for a clean selector. The wider language registry is installed but disabled until the administrator needs it.</div>
        </aside>

        <main class="card">
            @if($errors->any())<div class="error"><strong>Installation could not continue.</strong><div>{{ $errors->first() }}</div></div>@endif
            <form method="POST" action="{{ route('install.store') }}">
                @csrf

                <div class="section">
                    <div class="section-title"><span class="step">1</span> Company & platform</div>
                    <div class="fields">
                        <div><label for="app_name">Platform name</label><input id="app_name" name="app_name" value="{{ old('app_name','Financial Platform') }}" required></div>
                        <div><label for="company_name">Company / operator name</label><input id="company_name" name="company_name" value="{{ old('company_name') }}" required></div>
                        <div class="full"><label for="legal_company_name">Legal company name <span style="color:#7f8999;font-weight:400">(optional)</span></label><input id="legal_company_name" name="legal_company_name" value="{{ old('legal_company_name') }}"></div>
                        <div class="full"><label for="site_tagline">Tagline</label><input id="site_tagline" name="site_tagline" value="{{ old('site_tagline','Markets, intelligence and financial control.') }}" required></div>
                        <div class="full"><label for="site_description">Company description</label><textarea id="site_description" name="site_description" required>{{ old('site_description','A modern financial platform for markets, portfolio management, intelligent signals, automation and private investments.') }}</textarea></div>
                        <div><label for="support_email">Support email</label><input id="support_email" type="email" name="support_email" value="{{ old('support_email') }}" required></div>
                        <div><label for="support_phone">Support phone</label><input id="support_phone" name="support_phone" value="{{ old('support_phone') }}"></div>
                        <div class="full"><label for="app_url">Primary website URL</label><input id="app_url" type="url" name="app_url" value="{{ old('app_url',request()->getSchemeAndHttpHost()) }}" required></div>
                        <div class="full"><label for="app_env">Operating environment</label><select id="app_env" name="app_env"><option value="production" @selected(old('app_env','production')==='production')>Production</option><option value="local" @selected(old('app_env')==='local')>Development</option></select></div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">2</span> Branding</div>
                    <div class="colors">
                        <div class="color-field"><input id="brand_primary_color" type="color" name="brand_primary_color" value="{{ old('brand_primary_color','#c8102e') }}"><div><label for="brand_primary_color" style="margin:0">Primary color</label><div class="hint" id="primary-value">{{ old('brand_primary_color','#c8102e') }}</div></div></div>
                        <div class="color-field"><input id="brand_secondary_color" type="color" name="brand_secondary_color" value="{{ old('brand_secondary_color','#7c3aed') }}"><div><label for="brand_secondary_color" style="margin:0">Secondary accent</label><div class="hint" id="secondary-value">{{ old('brand_secondary_color','#7c3aed') }}</div></div></div>
                    </div>
                    <div class="preview" style="margin-top:12px"><div class="preview-top"><strong>Brand preview</strong><div class="hint">Shell accent and action hierarchy</div></div><div class="preview-body"><span class="preview-btn">Primary action</span></div></div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">3</span> Language & region</div>
                    <div class="fields">
                        <div><label for="default_locale">Default language</label><select id="default_locale" name="default_locale">@foreach($languageRegistry as $code=>$language)<option value="{{ $code }}" @selected(old('default_locale','en')===$code)>{{ $language['native_name'] }} · {{ $language['name'] }}</option>@endforeach</select></div>
                        <div><label for="default_currency">Default currency</label><select id="default_currency" name="default_currency">@foreach($currencies as $code=>$name)<option value="{{ $code }}" @selected(old('default_currency','USD')===$code)>{{ $code }} · {{ $name }}</option>@endforeach</select></div>
                        <div class="full"><label for="default_timezone">Timezone</label><select id="default_timezone" name="default_timezone">@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('default_timezone','UTC')===$timezone)>{{ $timezone }}</option>@endforeach</select></div>
                        <div class="full">
                            <label>Languages shown to customers</label>
                            @php $enabledOld=old('enabled_locales',$majorLocales); @endphp
                            <div class="language-major">@foreach($majorLocales as $code) @php $language=$languageRegistry[$code]; @endphp <label class="lang-check"><input type="checkbox" name="enabled_locales[]" value="{{ $code }}" @checked(in_array($code,$enabledOld,true))><span>{{ $language['native_name'] }} <small style="color:#7f8999">{{ $language['name'] }}</small></span></label>@endforeach</div>
                            <details class="advanced"><summary>Additional installed languages</summary><div class="minor-grid">@foreach($languageRegistry as $code=>$language) @continue(in_array($code,$majorLocales,true)) <label class="lang-check"><input type="checkbox" name="enabled_locales[]" value="{{ $code }}" @checked(in_array($code,$enabledOld,true))><span>{{ $language['native_name'] }}</span></label>@endforeach</div></details>
                            <div class="hint">Disabled languages stay installed in the Localization Center and can be enabled later without reinstalling the platform.</div>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">4</span> Database</div>
                    <div class="fields">
                        <div><label for="db_host">Host</label><input id="db_host" name="db_host" value="{{ old('db_host','127.0.0.1') }}" required></div><div><label for="db_port">Port</label><input id="db_port" type="number" name="db_port" value="{{ old('db_port','3306') }}" required></div><div><label for="db_database">Database name</label><input id="db_database" name="db_database" value="{{ old('db_database') }}" required></div><div><label for="db_username">Username</label><input id="db_username" name="db_username" value="{{ old('db_username','root') }}" required></div><div class="full"><label for="db_password">Database password</label><input id="db_password" type="password" name="db_password" autocomplete="new-password"></div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title"><span class="step">5</span> Administrator</div>
                    <div class="fields">
                        <div><label for="admin_name">Administrator name</label><input id="admin_name" name="admin_name" value="{{ old('admin_name','Platform Administrator') }}" required></div><div><label for="admin_email">Administrator email</label><input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required></div><div><label for="admin_password">Password</label><input id="admin_password" type="password" name="admin_password" minlength="10" required></div><div><label for="admin_password_confirmation">Confirm password</label><input id="admin_password_confirmation" type="password" name="admin_password_confirmation" minlength="10" required></div>
                    </div>
                </div>

                <button class="submit" type="submit" @disabled(!$allRequirementsMet)>Install platform</button>
            </form>
        </main>
    </div>
</div>
<script>
(()=>{const p=document.getElementById('brand_primary_color'),s=document.getElementById('brand_secondary_color'),pv=document.getElementById('primary-value'),sv=document.getElementById('secondary-value');function sync(){document.documentElement.style.setProperty('--accent',p.value);document.documentElement.style.setProperty('--secondary',s.value);pv.textContent=p.value;sv.textContent=s.value}p.addEventListener('input',sync);s.addEventListener('input',sync);sync();})();
</script>
</body>
</html>

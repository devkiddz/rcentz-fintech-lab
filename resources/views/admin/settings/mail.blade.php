<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="ui-heading">Mail & Notifications</h1>
            <p class="ui-lead max-w-3xl">Configure the application mail transport from the admin control plane. SMTP secrets are encrypted before they are stored and are never rendered back into the browser.</p>
        </div>
    </section>

    <main class="min-w-0 space-y-4">
            @include('admin.settings.partials.flash')

            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="ui-kicker">Email delivery</p>
                            <h2 class="mt-1 text-sm font-semibold">Mail configuration</h2>
                            <p class="mt-1 text-[10px] text-muted-foreground">Saved values override environment mail settings at runtime. Environment configuration remains the fallback.</p>
                        </div>
                        <span class="rounded-full border border-border bg-muted px-3 py-1 text-[9px] font-semibold uppercase tracking-[.1em]">
                            {{ $mailConfig['database_override'] ? 'Admin override' : 'Environment fallback' }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="p-5">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">Mail transport</label>
                            <select name="mail_mailer" class="ui-input mt-2 w-full" required>
                                @foreach(['smtp' => 'SMTP · Real delivery', 'log' => 'Log · Development only', 'array' => 'Array · Test only'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('mail_mailer', $mailConfig['mailer']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">SMTP security</label>
                            <select name="mail_scheme" class="ui-input mt-2 w-full">
                                <option value="auto" {{ old('mail_scheme', $mailConfig['scheme']) === 'auto' ? 'selected' : '' }}>Automatic TLS / STARTTLS when offered</option>
                                <option value="smtps" {{ old('mail_scheme', $mailConfig['scheme']) === 'smtps' ? 'selected' : '' }}>SMTPS · Implicit TLS</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">SMTP host</label>
                            <input type="text" name="mail_host" value="{{ old('mail_host', $mailConfig['host']) }}" placeholder="smtp.example.com" class="ui-input mt-2 w-full">
                        </div>

                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">SMTP port</label>
                            <input type="number" min="1" max="65535" name="mail_port" value="{{ old('mail_port', $mailConfig['port']) }}" placeholder="587" class="ui-input mt-2 w-full">
                        </div>

                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">SMTP username</label>
                            <input type="text" name="mail_username" value="{{ old('mail_username', $mailConfig['username']) }}" autocomplete="username" placeholder="mailer@example.com" class="ui-input mt-2 w-full">
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">SMTP password</label>
                                <span class="text-[8px] font-medium {{ $mailConfig['password_configured'] ? 'text-emerald-600' : 'text-muted-foreground' }}">
                                    {{ $mailConfig['password_saved'] ? 'Encrypted override saved' : ($mailConfig['password_configured'] ? 'Environment credential available' : 'Not configured') }}
                                </span>
                            </div>
                            <input type="password" name="mail_password" value="" autocomplete="new-password" placeholder="Leave blank to keep current password" class="ui-input mt-2 w-full">
                            <p class="mt-1 text-[8px] text-muted-foreground">For security, an existing password is never displayed. Blank keeps the current credential.</p>
                        </div>

                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">From address</label>
                            <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $mailConfig['from_address']) }}" placeholder="noreply@example.com" class="ui-input mt-2 w-full" required>
                        </div>

                        <div>
                            <label class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">From name</label>
                            <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $mailConfig['from_name']) }}" placeholder="{{ site_name() }}" class="ui-input mt-2 w-full" required>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-4">
                        <p class="max-w-2xl text-[9px] leading-4 text-muted-foreground">This does not edit <code>.env</code>. The control plane stores operational overrides in the database; SMTP passwords are encrypted with the application's encryption key.</p>
                        <button class="ui-btn ui-btn-primary">Save Email Configuration</button>
                    </div>
                </form>
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.2fr_.8fr]">
                <div class="ui-panel p-5">
                    <p class="ui-kicker">Runtime status</p>
                    <h2 class="mt-1 text-sm font-semibold">Active transport</h2>
                    <div class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-border sm:grid-cols-4">
                        @foreach([
                            ['Mailer', strtoupper($mailConfig['mailer'] ?: '—')],
                            ['Host', $mailConfig['host'] ?: 'Not required'],
                            ['Port', $mailConfig['port'] ?: '—'],
                            ['From', $mailConfig['from_address'] ?: '—'],
                        ] as [$label,$value])
                            <div class="bg-background p-3">
                                <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                                <p class="mt-1 truncate text-[10px] font-semibold">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="ui-panel p-5">
                    <p class="ui-kicker">Delivery check</p>
                    <h2 class="mt-1 text-sm font-semibold">Send a test email</h2>
                    <p class="mt-1 text-[9px] text-muted-foreground">The test uses the currently saved runtime configuration.</p>
                    <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="email" name="test_email" required placeholder="you@example.com" class="ui-input w-full">
                        <button class="ui-btn ui-btn-secondary w-full justify-center">Send Test Email</button>
                    </form>
                </div>
            </section>
    </main>
</div>
</x-admin-layout>

<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="ui-heading">{{ $sectionTitle }}</h1>
            <p class="ui-lead max-w-3xl">{{ $sectionDescription }}</p>
        </div>
    </section>

    <main class="min-w-0">
            @include('admin.settings.partials.flash')

            <form method="POST" action="{{ route($updateRoute) }}" enctype="multipart/form-data" class="ui-panel overflow-hidden">
                @csrf
                @method('PATCH')

                <div class="border-b border-border px-4 py-3">
                    <h2 class="text-sm font-semibold">{{ $sectionTitle }}</h2>
                    <p class="mt-1 text-[10px] text-muted-foreground">Only settings from this domain are saved by this form.</p>
                </div>

                @if($sectionSettings->count())
                    <div class="grid gap-5 p-5 md:grid-cols-2">
                        @foreach($sectionSettings as $setting)
                            <div class="space-y-2 {{ $setting->isTextarea() ? 'md:col-span-2' : '' }}">
                                <label class="block text-[10px] font-semibold text-foreground">
                                    {{ $setting->label }}
                                    @if($setting->description)
                                        <span class="mt-1 block text-[9px] font-normal leading-4 text-muted-foreground">{{ $setting->description }}</span>
                                    @endif
                                </label>

                                @if($setting->isCheckbox())
                                    <label class="flex items-center gap-3 rounded-xl border border-border bg-muted/10 p-3">
                                        <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                        <input type="checkbox" name="settings[{{ $setting->key }}]" value="1"
                                               {{ $setting->value == '1' ? 'checked' : '' }}
                                               class="h-4 w-4 rounded border-border">
                                        <span class="text-[10px] font-medium">Enable {{ $setting->label }}</span>
                                    </label>
                                @elseif($setting->isImage())
                                    <div class="rounded-xl border border-border p-3">
                                        @if($setting->value)
                                            <div class="mb-3 flex items-center gap-3">
                                                <img src="{{ asset('storage/' . $setting->value) }}" alt="{{ $setting->label }}" class="h-14 w-14 rounded-lg border border-border object-contain">
                                                <div class="min-w-0">
                                                    <p class="text-[9px] text-muted-foreground">Current file</p>
                                                    <p class="truncate text-[10px] font-medium">{{ basename($setting->value) }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        <input type="file" name="file_{{ $setting->key }}" accept="image/*,.svg,.ico" class="block w-full text-[10px] text-muted-foreground">
                                    </div>
                                @elseif($setting->isPassword())
                                    <input type="password" name="settings[{{ $setting->key }}]" value="" autocomplete="new-password"
                                           placeholder="{{ $setting->value ? '•••••••• (leave blank to keep current)' : 'Enter secret' }}"
                                           class="ui-input w-full">
                                @elseif($setting->isTextarea())
                                    <textarea name="settings[{{ $setting->key }}]" rows="4" class="ui-input w-full">{{ $setting->value }}</textarea>
                                @elseif($setting->type === 'color')
                                    <div class="flex items-center gap-3 rounded-xl border border-border bg-muted/10 p-3">
                                        <input type="color" name="settings[{{ $setting->key }}]" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', (string)$setting->value) ? $setting->value : '#c8102e' }}" class="h-10 w-14 cursor-pointer rounded-lg border border-border bg-transparent p-1">
                                        <div>
                                            <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Current color</p>
                                            <p class="mt-1 text-xs font-semibold uppercase">{{ $setting->value ?: '#c8102e' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="ui-input w-full">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end border-t border-border px-5 py-4">
                        <button class="ui-btn ui-btn-primary">Save {{ $sectionTitle }}</button>
                    </div>
                @else
                    <div class="p-8 text-center">
                        <p class="text-sm font-medium">No editable settings in this domain yet.</p>
                        <p class="mt-1 text-xs text-muted-foreground">The route is ready; settings can be attached here when the domain requires them.</p>
                    </div>
                @endif
            </form>
    </main>
</div>
</x-admin-layout>

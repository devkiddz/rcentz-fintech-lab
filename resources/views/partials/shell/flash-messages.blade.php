@php
    $shellAlerts = [
        'success' => ['icon' => 'check-circle', 'classes' => 'shell-alert-success'],
        'error' => ['icon' => 'alert-circle', 'classes' => 'shell-alert-error'],
        'warning' => ['icon' => 'alert-triangle', 'classes' => 'shell-alert-warning'],
        'info' => ['icon' => 'info', 'classes' => 'shell-alert-info'],
    ];
@endphp

@foreach($shellAlerts as $type => $meta)
    @if(session($type))
        <div class="shell-alert {{ $meta['classes'] }}" role="alert">
            <i data-lucide="{{ $meta['icon'] }}" class="h-4 w-4 shrink-0"></i>
            <span class="min-w-0 flex-1 text-sm">{{ session($type) }}</span>
            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="shell-alert-close"
                aria-label="Dismiss message"
            >
                <i data-lucide="x" class="h-3.5 w-3.5"></i>
            </button>
        </div>
    @endif
@endforeach

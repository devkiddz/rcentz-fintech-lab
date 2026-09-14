<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support Request</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'; color: #111827; }
        .muted { color: #6B7280; }
        .badge { background: #111827; color: #fff; padding: 2px 6px; border-radius: 6px; font-size: 12px; }
    </style>
    </head>
<body>
    <h2>New Support Request</h2>
    <p><span class="badge">{{ $category }}</span></p>
    <p><strong>From:</strong> {{ $user->name }} &lt;{{ $user->email }}&gt;</p>
    <p><strong>Subject:</strong> {{ $subject }}</p>
    <hr>
    <div>
        {!! nl2br(e($bodyMessage)) !!}
    </div>
    <hr>
    <p class="muted">Sent from {{ site_name() }}</p>
</body>
</html>



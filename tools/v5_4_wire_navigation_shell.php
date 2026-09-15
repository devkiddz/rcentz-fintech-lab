<?php

function replaceBetween(string $text, string $start, string $end, string $replacement, string $label): string {
    $startPos = strpos($text, $start);
    if ($startPos === false) {
        throw new RuntimeException("Missing start anchor: {$label}");
    }

    $endPos = strpos($text, $end, $startPos);
    if ($endPos === false) {
        throw new RuntimeException("Missing end anchor: {$label}");
    }

    return substr($text, 0, $startPos)
        . $replacement
        . substr($text, $endPos);
}

$root = dirname(__DIR__);

// Customer layout
$userPath = $root.'/resources/views/layouts/user-layout.blade.php';
$user = file_get_contents($userPath);

$user = replaceBetween(
    $user,
    '                    <!-- Grouped Navigation (mobile-first reference shell) -->',
    '                    <!-- Enhanced User Menu -->',
    "                    <!-- Modern grouped workspace navigation -->\n".
    "                    @include('partials.shell.customer-sidebar-nav')\n\n".
    "                    ",
    'customer navigation'
);

$user = str_replace(
    '<div class="flex-1 flex min-w-0 flex-col overflow-hidden lg:ml-72 bg-background">',
    '<div id="workspace-main" class="flex-1 flex min-w-0 flex-col overflow-hidden lg:ml-72 bg-background">',
    $user
);

if (!str_contains($user, "partials.shell.sidebar-behavior")) {
    $user = str_replace(
        '</head>',
        "        @include('partials.shell.sidebar-behavior')\n    </head>",
        $user
    );
}

file_put_contents($userPath, $user);

// Admin layout
$adminPath = $root.'/resources/views/layouts/admin-layout.blade.php';
$admin = file_get_contents($adminPath);

// Modernize old permanent-black sidebar into theme-aware card surface.
$admin = str_replace(
    'class="fixed inset-y-0 left-0 z-50 w-72 bg-black shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0"',
    'class="fixed inset-y-0 left-0 z-50 w-72 bg-card text-foreground shadow-sm transform -translate-x-full transition-all duration-300 ease-in-out lg:translate-x-0 border-r border-border"',
    $admin
);
$admin = str_replace(
    'class="flex items-center justify-between h-16 px-4 border-b border-gray-800"',
    'class="flex items-center justify-between h-16 px-4 border-b border-border"',
    $admin
);

$admin = replaceBetween(
    $admin,
    '                    <!-- Navigation -->',
    '                    <!-- User Menu -->',
    "                    <!-- Modern grouped workspace navigation -->\n".
    "                    @include('partials.shell.admin-sidebar-nav')\n\n".
    "                    ",
    'admin navigation'
);

// Theme-aware admin user-menu shell. Keep its existing content but neutralize the hard black divider.
$admin = str_replace(
    '<div class="p-4 border-t border-gray-800">',
    '<div class="p-4 border-t border-border">',
    $admin
);

$admin = str_replace(
    '<div class="flex-1 flex flex-col overflow-hidden lg:ml-72">',
    '<div id="workspace-main" class="flex-1 flex flex-col overflow-hidden lg:ml-72">',
    $admin
);

if (!str_contains($admin, "partials.shell.sidebar-behavior")) {
    $admin = str_replace(
        '</head>',
        "        @include('partials.shell.sidebar-behavior')\n    </head>",
        $admin
    );
}

file_put_contents($adminPath, $admin);

echo "V5.4 admin + customer navigation shells wired.\n";

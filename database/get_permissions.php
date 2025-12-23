<?php
// database/get_permissions.php
// Removed: session_start(); // This line was causing the "session already active" notice

function getPermissions($role) {
    $permissions = [
        'admin' => [
            'dashboard' => ['view'],
            'products' => ['view', 'add', 'edit', 'delete'],
            'stocks' => ['view', 'add', 'edit', 'delete'],
            'orders' => ['view', 'add', 'edit', 'delete'],
            'order_items' => ['view'],
            'users' => ['view', 'add', 'edit', 'delete'],
            'reports' => ['view']
        ],
        'staff' => [
            'dashboard' => ['view'],
            'products' => ['view'],
            'stocks' => ['view'],
            'orders' => ['view', 'add'],
            'order_items' => ['view'],
            'users' => [],
            'reports' => []
        ],
        'guest' => [
            'dashboard' => [],
            'products' => [],
            'stocks' => [],
            'orders' => [],
            'order_items' => [],
            'users' => [],
            'reports' => []
        ]
    ];

    return $permissions[$role] ?? $permissions['guest'];
}
?>
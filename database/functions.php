<?php
require_once 'connect.php';

/**
 * Fetches all permissions for a given user ID.
 * Returns an associative array like:
 * [
 *   'user' => ['create', 'edit'],
 *   'product' => ['view'],
 *   ...
 * ]
 */
function fetch_user_permissions($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT module, action FROM permissions WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $permissions = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $module = $row['module'];
        $action = $row['action'];
        if (!isset($permissions[$module])) {
            $permissions[$module] = [];
        }
        $permissions[$module][] = $action;
    }

    return $permissions;
}

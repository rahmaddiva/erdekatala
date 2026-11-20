<?php

use App\Models\RoleModel;

if (! function_exists('current_role_row')) {
    function current_role_row(): ?array
    {
        static $rows = [];

        $name = session()->get('role');
        if (!$name) {
            return null;
        }
        if (!array_key_exists($name, $rows)) {
            $rows[$name] = (new RoleModel())->find($name);
        }

        return $rows[$name];
    }
}

if (! function_exists('role_scope')) {
    /** Scope data role aktif: dinas (semua) | kecamatan | desa. */
    function role_scope(): string
    {
        $scope = current_role_row()['scope'] ?? null;

        return in_array($scope, ['dinas', 'kecamatan', 'desa'], true) ? $scope : 'desa';
    }
}

if (! function_exists('can')) {
    /** Cek permission role aktif (di-cache per request). */
    function can(string $permission): bool
    {
        static $cache = [];

        $name = session()->get('role');
        if (!$name) {
            return false;
        }
        if (!isset($cache[$name])) {
            $cache[$name] = (new RoleModel())->permissionsOf($name);
        }

        return in_array($permission, $cache[$name], true);
    }
}

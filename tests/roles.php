<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Parts\Guild\Role;
use Discord\Parts\Permissions\RolePermission;

startTest('Create Role');

$role = new Role();

try {
    $role->guild_id = $baseGuild->id;
    $role->save();
} catch (\Exception $e) {
    fail($e);
}

pass();

startTest('Edit Role');

$p                = new RolePermission();
$p->manage_roles  = true;
$updateAttributes = [
    'name'        => 'newname',
    'hoist'       => false,
    'color'       => 12745742,
    'permissions' => $p,
];

try {
    $role->fill($updateAttributes);
    $role->save();
} catch (\Exception $e) {
    fail($e);
}

checkAttributes($updateAttributes, $role);

pass();

startTest('Delete Role');

try {
    $role->delete();
} catch (\Exception $e) {
    fail($e);
}

if ($baseGuild->roles->get('id', $role->id) !== null) {
    fail('Deleting the role did not work.');
}

pass();

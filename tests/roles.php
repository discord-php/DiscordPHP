<?php

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

$p = new RolePermission();
$p->manage_roles = true;
$updateAttributes = [
	'name' => 'newname',
	'hoist' => false,
	'color' => 12745742,
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
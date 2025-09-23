<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['admin','epi_manager','user'] as $r) {
            Role::findOrCreate($r);
        }

        $perms = [
            'epi.view','epi.create','epi.update','epi.delete',
            'epi.assign','epi.inspect',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p);
        }

        Role::where('name','epi_manager')->first()?->givePermissionTo($perms);
        Role::where('name','admin')->first()?->givePermissionTo($perms);
    }
}

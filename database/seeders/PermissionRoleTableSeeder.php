<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_permissions = Permission::whereIn('title', [
            'admin_access',
            'agent_access',
            'agent_index',
            'agent_create',
            'agent_edit',
            'agent_delete',
            'agent_change_password_access',
            'transfer_log',
            'make_transfer',
            'game_type_access',
        ]);
        Role::findOrFail(1)->permissions()->sync($admin_permissions->pluck('id'));
        // Admin permissions
        // Agent gets specific permissions
        $agent_permissions = Permission::whereIn('title', [
            'agent_access',
            'agent_index',
            'agent_create',
            'agent_edit',
            'agent_delete',
            'agent_update',
            'agent_change_password_access',
            'player_index',
            'player_create',
            'player_edit',
            'player_delete',
            'transfer_log',
            'make_transfer',
            'withdraw',
            'deposit',
            'bank',
            'site_logo',
        ])->pluck('id');

        Role::findOrFail(2)->permissions()->sync($agent_permissions);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermmisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect([
            ['name' => 'view dashboard', 'group' => 'dashboard', 'parent' => ''],
            ['name' => 'view administration', 'group' => 'administration', 'parent' => 'administration'],
            ['name' => 'view users', 'group' => 'users', 'parent' => 'administration'],
            ['name' => 'create user', 'group' => 'users', 'parent' => 'administration'],
            ['name' => 'edit user', 'group' => 'users', 'parent' => 'administration'],
            ['name' => 'view roles', 'group' => 'roles', 'parent' => 'administration'],
            ['name' => 'create role', 'group' => 'roles', 'parent' => 'administration'],
            ['name' => 'edit role', 'group' => 'roles', 'parent' => 'administration'],
            ['name' => 'view permissions', 'group' => 'permissions', 'parent' => 'administration'],
            ['name' => 'create permission', 'group' => 'permissions', 'parent' => 'administration'],
            ['name' => 'edit permission', 'group' => 'permissions', 'parent' => 'administration'],

            // Marketplace: categories
            ['name' => 'view categories', 'group' => 'categories', 'parent' => 'administration'],
            ['name' => 'create category', 'group' => 'categories', 'parent' => 'administration'],
            ['name' => 'edit category', 'group' => 'categories', 'parent' => 'administration'],
            ['name' => 'delete category', 'group' => 'categories', 'parent' => 'administration'],

            // Marketplace: subscriptions & billing
            ['name' => 'view subscriptions', 'group' => 'subscriptions', 'parent' => 'administration'],
            ['name' => 'edit subscription plans', 'group' => 'subscriptions', 'parent' => 'administration'],
            ['name' => 'manage provider subscription', 'group' => 'subscriptions', 'parent' => 'administration'],

            // Marketplace: moderation
            ['name' => 'moderate listings', 'group' => 'moderation', 'parent' => 'administration'],
            ['name' => 'moderate reviews', 'group' => 'moderation', 'parent' => 'administration'],
            ['name' => 'moderate providers', 'group' => 'moderation', 'parent' => 'administration'],
            ['name' => 'moderate quote requests', 'group' => 'moderation', 'parent' => 'administration'],

            // Marketplace: leads
            ['name' => 'view quote requests', 'group' => 'leads', 'parent' => 'administration'],

            // Furnizor (provider) self-service
            ['name' => 'view provider dashboard', 'group' => 'furnizor', 'parent' => ''],
            ['name' => 'manage own profile', 'group' => 'furnizor', 'parent' => ''],
            ['name' => 'manage own listings', 'group' => 'furnizor', 'parent' => ''],
            ['name' => 'manage own subscription', 'group' => 'furnizor', 'parent' => ''],

            // Client self-service
            ['name' => 'manage own favorites', 'group' => 'client', 'parent' => ''],
            ['name' => 'submit quote request', 'group' => 'client', 'parent' => ''],
            ['name' => 'submit review', 'group' => 'client', 'parent' => ''],
        ])->each(function ($factory) {
            Permission::firstOrCreate(
                ['name' => $factory['name'], 'guard_name' => 'web'],
                $factory
            );
        });

        Role::get()->each(function ($role) {

            if ($role->name === 'admin') {
                $role->syncPermissions(Permission::whereIn('group', [
                    'dashboard', 'administration', 'users', 'roles', 'permissions',
                    'categories', 'subscriptions', 'moderation', 'leads',
                ])->pluck('id'));
            } elseif ($role->name === 'supervisor') {
                $role->syncPermissions(Permission::whereIn('group', [
                    'dashboard', 'administration', 'users',
                ])->pluck('id'));
            } elseif ($role->name === 'user') {
                $role->syncPermissions(Permission::whereIn('name', [
                    'view dashboard'
                ])->pluck('id'));
            } elseif ($role->name === 'furnizor') {
                $role->syncPermissions(Permission::whereIn('group', ['furnizor'])
                    ->orWhere('name', 'view dashboard')
                    ->pluck('id'));
            } elseif ($role->name === 'client') {
                $role->syncPermissions(Permission::whereIn('group', ['client'])
                    ->orWhere('name', 'view dashboard')
                    ->pluck('id'));
            }
        });
    }
}

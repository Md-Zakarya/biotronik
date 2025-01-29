<?php
namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'roles' => 'required|array'
        ]);

        $user = \App\Models\User::find($request->user_id);
        $user->syncRoles($request->roles);

        return response()->json(['message' => 'Roles assigned successfully']);
    }

    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        Role::create(['name' => $request->name]);
        return response()->json(['message' => 'Role created successfully']);
    }
}
<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:vendor,customer',
            'business_name' => 'required_if:role,vendor|string|max:255',
            'business_description' => 'nullable|string',
        ]);

        // Default to customer role if not specified
        $roleName = $request->get('role', 'customer');
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json(['message' => 'Invalid role selected'], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role->id,
            'is_seller' => $request->role === 'vendor',
        ]);

        // Create vendor profile if registering as vendor
        if ($request->role === 'vendor') {
            VendorProfile::create([
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_description' => $request->business_description,
                'status' => 'pending',
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load('role', 'vendorProfile');

        return response()->json([
            'user' => $user,
            'token' => $token,
            'dashboard_url' => $this->getDashboardUrl($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::with('role', 'vendorProfile')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'dashboard_url' => $this->getDashboardUrl($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('role', 'vendorProfile');

        return response()->json([
            'user' => $user,
            'dashboard_url' => $this->getDashboardUrl($user),
        ]);
    }

    private function getDashboardUrl($user)
    {
        switch ($user->role->name) {
            case Role::ADMIN:
                return '/admin/dashboard';
            case Role::VENDOR:
                return '/vendor/dashboard';
            case Role::CUSTOMER:
            default:
                return '/customer/dashboard';
        }
    }
}
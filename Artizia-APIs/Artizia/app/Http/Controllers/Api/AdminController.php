<?php
// app/Http/Controllers/Api/AdminController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_vendors' => User::vendors()->count(),
            'total_customers' => User::customers()->count(),
            'total_products' => Product::count(),
            'active_products' => Product::active()->count(),
            'total_categories' => Category::count(),
            'pending_vendors' => VendorProfile::pending()->count(),
        ];

        $recent_users = User::with('role')->latest()->take(5)->get();
        $recent_products = Product::with('user', 'category')->latest()->take(5)->get();
        $pending_vendors = VendorProfile::with('user')->pending()->latest()->take(5)->get();

        return response()->json([
            'stats' => $stats,
            'recent_users' => $recent_users,
            'recent_products' => $recent_products,
            'pending_vendors' => $pending_vendors,
        ]);
    }

    public function users(Request $request)
    {
        $query = User::with('role', 'vendorProfile');

        if ($request->has('role')) {
            $query->byRole($request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        return response()->json($users);
    }

    public function vendors(Request $request)
    {
        $query = VendorProfile::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $vendors = $query->paginate(15);

        return response()->json($vendors);
    }

    public function approveVendor(Request $request, VendorProfile $vendor)
    {
        $vendor->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Vendor approved successfully',
            'vendor' => $vendor->load('user')
        ]);
    }

    public function rejectVendor(Request $request, VendorProfile $vendor)
    {
        $vendor->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Vendor rejected',
            'vendor' => $vendor->load('user')
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with('user', 'category');

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('vendor')) {
            $query->where('user_id', $request->vendor);
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->paginate(20);

        return response()->json($products);
    }

    public function toggleProductStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        return response()->json([
            'message' => $product->is_active ? 'Product activated' : 'Product deactivated',
            'product' => $product
        ]);
    }
}
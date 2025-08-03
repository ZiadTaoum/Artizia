<?php
// app/Http/Controllers/Api/CustomerController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['products', 'product', 'vendors', 'vendor', 'categories']);
    }

    public function dashboard(Request $request)
    {
        $featured_products = Product::active()
            ->featured()
            ->with('category', 'vendor', 'primaryImage')
            ->take(8)
            ->get();

        $recent_products = Product::active()
            ->with('category', 'vendor', 'primaryImage')
            ->latest()
            ->take(8)
            ->get();

        $categories = Category::active()
            ->parents()
            ->withCount('products')
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        return response()->json([
            'featured_products' => $featured_products,
            'recent_products' => $recent_products,
            'categories' => $categories,
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::active()->with('category', 'vendor', 'primaryImage');

        // Filter by category
        if ($request->has('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by vendor
        if ($request->has('vendor')) {
            $query->where('user_id', $request->vendor);
        }

        // Price range filter
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate(20);

        return response()->json($products);
    }

    public function product($slug)
    {
        $product = Product::active()
            ->with('category', 'vendor.vendorProfile', 'images')
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Get related products from same category
        $related_products = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('category', 'vendor', 'primaryImage')
            ->take(4)
            ->get();

        // Get other products from same vendor
        $vendor_products = Product::active()
            ->where('user_id', $product->user_id)
            ->where('id', '!=', $product->id)
            ->with('category', 'primaryImage')
            ->take(4)
            ->get();

        return response()->json([
            'product' => $product,
            'related_products' => $related_products,
            'vendor_products' => $vendor_products,
        ]);
    }

    public function vendors(Request $request)
    {
        $query = VendorProfile::approved()->with('user');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('business_description', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $vendors = $query->paginate(20);

        return response()->json($vendors);
    }

    public function vendor($id)
    {
        $vendor = VendorProfile::approved()
            ->with('user')
            ->where('user_id', $id)
            ->first();

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        // Get vendor's products
        $products = Product::active()
            ->where('user_id', $id)
            ->with('category', 'primaryImage')
            ->paginate(12);

        // Get vendor stats
        $stats = [
            'total_products' => Product::where('user_id', $id)->count(),
            'active_products' => Product::active()->where('user_id', $id)->count(),
            'categories_count' => Product::where('user_id', $id)
                ->distinct('category_id')
                ->count('category_id'),
        ];

        return response()->json([
            'vendor' => $vendor,
            'products' => $products,
            'stats' => $stats,
        ]);
    }

    public function categories()
    {
        $categories = Category::active()
            ->with(['children' => function($query) {
                $query->active()->withCount('products');
            }])
            ->withCount('products')
            ->parents()
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    public function category($slug)
    {
        $category = Category::active()
            ->with(['children' => function($query) {
                $query->active()->withCount('products');
            }])
            ->withCount('products')
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Get products in this category and its children
        $categoryIds = collect([$category->id])
            ->merge($category->children->pluck('id'))
            ->toArray();

        $products = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->with('category', 'vendor', 'primaryImage')
            ->paginate(20);

        return response()->json([
            'category' => $category,
            'products' => $products,
        ]);
    }
}
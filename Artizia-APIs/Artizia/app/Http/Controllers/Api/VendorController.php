<?php
// app/Http/Controllers/Api/VendorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function dashboard(Request $request)
    {
        $vendor = $request->user();

        $stats = [
            'total_products' => $vendor->products()->count(),
            'active_products' => $vendor->products()->active()->count(),
            'out_of_stock' => $vendor->products()->where('inventory_quantity', 0)->count(),
            'featured_products' => $vendor->products()->featured()->count(),
        ];

        $recent_products = $vendor->products()
            ->with('category', 'primaryImage')
            ->latest()
            ->take(5)
            ->get();

        $low_stock_products = $vendor->products()
            ->where('inventory_quantity', '<=', 10)
            ->where('inventory_quantity', '>', 0)
            ->with('category')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_products' => $recent_products,
            'low_stock_products' => $low_stock_products,
            'vendor_profile' => $vendor->vendorProfile,
        ]);
    }

    public function products(Request $request)
    {
        $query = $request->user()->products()->with('category', 'primaryImage');

        if ($request->has('category')) {
            $query->where('category_id', $request->category);
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

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'sku' => 'nullable|string|unique:products,sku',
            'inventory_quantity' => 'required|integer|min:0',
            'min_order_quantity' => 'nullable|integer|min:1',
            'max_order_quantity' => 'nullable|integer|gt:min_order_quantity',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $productData = $request->except(['images']);
        $productData['user_id'] = $request->user()->id;
        $productData['is_active'] = true;

        // Handle dimensions
        if ($request->has('dimensions')) {
            $productData['dimensions'] = $request->dimensions;
        }

        $product = Product::create($productData);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'alt_text' => $product->name,
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('category', 'images')
        ], 201);
    }

    public function showProduct(Product $product)
    {
        // Ensure vendor can only view their own products
        if ($product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($product->load('category', 'images'));
    }

    public function updateProduct(Request $request, Product $product)
    {
        // Ensure vendor can only update their own products
        if ($product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'inventory_quantity' => 'required|integer|min:0',
            'min_order_quantity' => 'nullable|integer|min:1',
            'max_order_quantity' => 'nullable|integer|gt:min_order_quantity',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $product->update($request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load('category', 'images')
        ]);
    }

    public function deleteProduct(Product $product)
    {
        // Ensure vendor can only delete their own products
        if ($product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function categories()
    {
        $categories = Category::active()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json($categories);
    }

    public function profile(Request $request)
    {
        $vendorProfile = $request->user()->vendorProfile;

        if (!$vendorProfile) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        return response()->json($vendorProfile);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_description' => 'nullable|string',
            'business_address' => 'nullable|string',
            'business_phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
        ]);

        $vendorProfile = $request->user()->vendorProfile;

        if (!$vendorProfile) {
            return response()->json(['message' => 'Vendor profile not found'], 404);
        }

        $vendorProfile->update($request->all());

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $vendorProfile
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /** Public marketplace browsing — only listings with an active paid window show up. */
    public function index(Request $request)
    {
        $cacheKey = 'products:index:' . md5($request->getQueryString() ?? '');

        return Cache::tags(['products'])->remember($cacheKey, 60, function () use ($request) {
            $query = Product::query()
                ->listingActive()
                ->with('seller:id,name,avatar,phone');

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            return $query->latest()->paginate(15);
        });
    }

    public function show(Product $product)
    {
        abort_unless($product->is_listing_active, 404, 'This listing is no longer available.');

        $product->load('seller:id,name,avatar,phone');

        return response()->json($product);
    }

    /** Any authenticated user can list a product — created inactive-until-paid (no expires_at yet). */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:4096',
        ]);

        $validated['image'] = $request->hasFile('image')
            ? $request->file('image')->store('products', 'r2')
            : null;

        $product = $request->user()->products()->create($validated);
        Cache::tags(['products'])->flush();

        return response()->json($product, 201);
    }

    /** Seller's own listings, including expired/unpaid ones they're managing. */
    public function mine(Request $request)
    {
        return response()->json(
            $request->user()->products()->latest()->paginate(20)
        );
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('r2')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'r2');
        } else {
            unset($validated['image']);
        }

        $product->update($validated);
        Cache::tags(['products'])->flush();

        return response()->json($product);
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeOwnership($request, $product);

        if ($product->image) {
            Storage::disk('r2')->delete($product->image);
        }

        $product->delete();
        Cache::tags(['products'])->flush();

        return response()->json(['message' => 'Listing removed']);
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        abort_unless($product->user_id === $request->user()->id, 403, 'You do not own this listing');
    }
}

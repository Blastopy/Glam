<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('seller:id,name,email,phone');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->latest()->paginate(25));
    }

    /** Pulls a listing down immediately — for inappropriate/prohibited items, independent of its paid window. */
    public function deactivate(Product $product)
    {
        $product->update(['is_active' => false]);
        Cache::tags(['products'])->flush();

        return response()->json($product);
    }

    public function reactivate(Product $product)
    {
        $product->update(['is_active' => true]);
        Cache::tags(['products'])->flush();

        return response()->json($product);
    }
}

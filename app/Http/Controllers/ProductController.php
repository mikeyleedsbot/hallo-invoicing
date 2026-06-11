<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Sorteerbare kolommen
        $allowedSorts = ['name', 'description', 'price'];
        $sort = in_array($request->query('sort'), $allowedSorts) ? $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $products = Product::orderBy($sort, $direction)->paginate(20)->withQueryString();

        return view('products.index', compact('products', 'sort', 'direction'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        Product::create($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product succesvol aangemaakt!');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product succesvol bijgewerkt!');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        
        return redirect()
            ->route('products.index')
            ->with('success', 'Product verwijderd!');
    }
}

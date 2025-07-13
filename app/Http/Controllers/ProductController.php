<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Models\Product;
use App\Services\Product\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request)
    {
        return new ProductCollection($this->products->paginate($request->all(), $request->user()));
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->products->create($request->validated(), $request->user(), $request->file('image'));

        return success(['product' => $product], 201);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $result = $this->products->update($product, $request->validated(), $request->user(), $request->file('image'));

        return success([
            'product' => $result['product'],
            'is_favorite' => $result['is_favorite'],
        ], 200, $result['message']);
    }

    public function show(Request $request, Product $product)
    {
        return success($this->products->show($product, $request->user()));
    }

    public function destroy(Request $request, Product $product)
    {
        $this->products->delete($product, $request->user());

        return success([], 200, 'Product deleted successfully.');
    }

    public function getFavorites(Request $request)
    {
        return success(['favorite products' => $this->products->favorites($request->user())], 200);
    }

    public function addToFavorites(Request $request, Product $product)
    {
        $this->products->addFavorite($request->user(), $product);

        return success([
            'product' => $product
        ], 200, 'Product added to your favorites');
    }


    public function removeFromFavorites(Request $request, Product $product)
    {
        $this->products->removeFavorite($request->user(), $product);

        return success([], 200, 'Product removed from your favorites');
    }
}

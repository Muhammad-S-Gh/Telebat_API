<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\CreateStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Resources\Store\StoreResource;
use App\Models\Store;
use App\Services\Store\StoreService;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct(private readonly StoreService $stores) {}

    public function index(Request $request)
    {
        $stores = $this->stores->paginate($request->only('search'));
        if ($stores->total() === 0) {
            return success(['pagination' => [
                'current_page' => $stores->currentPage(), 'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(), 'total' => $stores->total(),
            ]], 200, 'No stores found for the given criteria.');
        }
        if ($stores->currentPage() > $stores->lastPage()) {
            return redirect()->route('stores.index', ['page' => 1]);
        }

        return StoreResource::collection($stores);
    }

    public function store(CreateStoreRequest $request)
    {
        return success(['store' => $this->stores->create($request->validated(), $request->user(), $request->file('image'))], 201);
    }

    public function update(UpdateStoreRequest $request, Store $store)
    {
        $result = $this->stores->update($store, $request->validated(), $request->user(), $request->file('image'));
        return success(['store' => $result['store']], 200, $result['message'] ?? 'success');
    }

    public function show(Store $store)
    {
        return success(['store' => $store]);
    }

    public function destroy(Request $request, Store $store)
    {
        $this->stores->delete($store, $request->user());
        return success([], 200, 'store deleted successfully');
    }
}

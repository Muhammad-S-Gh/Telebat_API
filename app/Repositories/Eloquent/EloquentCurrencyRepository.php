<?php

namespace App\Repositories\Eloquent;

use App\Models\Currency;
use App\Repositories\Contracts\CurrencyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function all(): Collection
    {
        return Currency::all();
    }

    public function findActive(int $id): Currency
    {
        return Currency::where('active', true)->findOrFail($id);
    }
}

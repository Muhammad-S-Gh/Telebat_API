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
}

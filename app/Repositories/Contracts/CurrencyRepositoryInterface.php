<?php

namespace App\Repositories\Contracts;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface CurrencyRepositoryInterface
{
    public function all(): Collection;

    public function findActive(int $id): Currency;
}

<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface CurrencyRepositoryInterface
{
    public function all(): Collection;
}

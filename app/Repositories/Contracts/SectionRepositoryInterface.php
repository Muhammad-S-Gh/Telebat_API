<?php

namespace App\Repositories\Contracts;

use App\Models\Section;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SectionRepositoryInterface
{
    public function paginate(array $filters, string $locale, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): Section;

    public function save(Section $section): Section;

    public function delete(Section $section): bool;
}

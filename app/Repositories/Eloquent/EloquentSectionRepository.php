<?php

namespace App\Repositories\Eloquent;

use App\Models\Section;
use App\Repositories\Contracts\SectionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSectionRepository implements SectionRepositoryInterface
{
    public function paginate(array $filters, string $locale, int $perPage): LengthAwarePaginator
    {
        $jsonPath = '$.' . $locale;
        $search = $filters['search'] ?? null;

        return Section::query()
            ->when($search, function ($query) use ($search, $jsonPath) {
                $query->where(function ($nestedQuery) use ($jsonPath, $search) {
                    $nestedQuery
                        ->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(name, ?)) LIKE ?', [$jsonPath, "%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(description, ?)) LIKE ?', [$jsonPath, "%{$search}%"]);
                });
            })
            ->paginate($perPage);
    }

    public function create(array $attributes): Section
    {
        return Section::create($attributes);
    }

    public function save(Section $section): Section
    {
        $section->save();

        return $section->fresh();
    }

    public function delete(Section $section): bool
    {
        return (bool) $section->delete();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Section\StoreSectionRequest;
use App\Http\Requests\Section\UpdateSectionRequest;
use App\Http\Resources\Section\SectionResource;
use App\Models\Section;
use App\Services\Section\SectionService;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function __construct(private readonly SectionService $sections) {}

    public function index(Request $request)
    {
        $sections = $this->sections->paginate($request->only('search'));
        if ($sections->total() === 0) {
            return success(['pagination' => [
                'current_page' => $sections->currentPage(), 'last_page' => $sections->lastPage(),
                'per_page' => $sections->perPage(), 'total' => $sections->total(),
            ]], 200, 'No sections found for the given criteria.');
        }
        if ($sections->currentPage() > $sections->lastPage()) {
            return redirect()->route('sections.index', ['page' => 1]);
        }

        return SectionResource::collection($sections);
    }

    public function store(StoreSectionRequest $request)
    {
        return success(['section' => $this->sections->create($request->validated(), $request->file('image'))], 201);
    }

    public function show(Section $section)
    {
        return success(['section' => $section]);
    }

    public function update(UpdateSectionRequest $request, Section $section)
    {
        $result = $this->sections->update($section, $request->validated(), $request->file('image'));
        return success(['section' => $result['section']], 200, $result['message'] ?? 'success');
    }

    public function destroy(Section $section)
    {
        $this->sections->delete($section);
        return success(['message' => 'Section deleted successfully'], 200);
    }
}

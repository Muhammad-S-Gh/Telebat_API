<?php

namespace App\Services\Section;

use App\Models\Section;
use App\Repositories\Contracts\SectionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SectionService
{
    public function __construct(private readonly SectionRepositoryInterface $sections) {}

    public function paginate(array $filters)
    {
        return $this->sections->paginate(
            $filters,
            app()->getLocale(),
            config('pagination.per_page', 5)
        );
    }

    public function create(array $data, ?UploadedFile $image): array
    {
        $section = $this->sections->create([
            'name' => ['ar' => $data['ar_name'], 'en' => $data['en_name']],
            'description' => ['ar' => $data['ar_description'], 'en' => $data['en_description']],
            'image' => $image?->store('sections', 'public'),
        ]);

        return $this->localizedPayload($section, app()->getLocale());
    }

    public function update(Section $section, array $data, ?UploadedFile $image): array
    {
        if ($image) {
            $this->deleteImage($section->image);
            $section->image = $image->store('sections', 'public');
        }

        $this->mergeTranslation($section, 'name', $data['ar_name'] ?? null, $data['en_name'] ?? null);
        $this->mergeTranslation($section, 'description', $data['ar_description'] ?? null, $data['en_description'] ?? null);

        if (! $section->isDirty() && ! $image) {
            return ['section' => $section, 'message' => 'Nothing updated.'];
        }

        return ['section' => $this->sections->save($section), 'message' => null];
    }

    public function delete(Section $section): void
    {
        $this->deleteImage($section->image);
        $this->sections->delete($section);
    }

    private function localizedPayload(Section $section, string $locale): array
    {
        return [
            'name' => $section->getName($locale),
            'description' => $section->getDescription($locale),
            'image' => $section->image,
        ];
    }

    private function mergeTranslation(Section $section, string $field, ?string $ar, ?string $en): void
    {
        $override = array_filter(['ar' => $ar, 'en' => $en], fn ($value) => $value !== null);

        if ($override === []) {
            return;
        }

        $section->{$field} = array_merge(['ar' => null, 'en' => null], $section->{$field} ?? [], $override);
    }

    private function deleteImage(?string $image): void
    {
        if ($image && Storage::disk('public')->exists($image)) {
            Storage::disk('public')->delete($image);
        }
    }
}

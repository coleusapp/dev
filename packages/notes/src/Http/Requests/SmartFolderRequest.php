<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Markdown\Hashtags;
use Coleus\Notes\Models\Folder;
use Coleus\Notes\Search\SmartFolderRules;
use Illuminate\Foundation\Http\FormRequest;

class SmartFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'rules' => [
                'required',
                'array',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (is_array($value) && SmartFolderRules::normalize($this->cleanRules($value)) === SmartFolderRules::DEFAULTS) {
                        $fail('Choose at least one rule.');
                    }
                },
            ],
            'rules.tags' => ['array', 'max:10'],
            'rules.tags.*' => ['nullable', 'string', 'max:'.Hashtags::MAX_LENGTH],
            'rules.tags_match' => ['nullable', 'in:all,any'],
            'rules.folder_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && ! Folder::query()->whereKey($value)->exists()) {
                        $fail('That folder does not exist.');
                    }
                },
            ],
            'rules.edited_within_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'rules.created_within_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'rules.edited_from' => ['nullable', 'date_format:Y-m-d'],
            'rules.edited_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:rules.edited_from'],
            'rules.created_from' => ['nullable', 'date_format:Y-m-d'],
            'rules.created_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:rules.created_from'],
            'rules.pinned' => ['boolean'],
            'rules.has_checklist' => ['boolean'],
            'rules.has_open_tasks' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the smart folder a name.',
            'rules.tags.max' => 'Use at most 10 tags.',
            'rules.*_within_days.min' => 'Use a number of days from 1 to 3650.',
            'rules.*_within_days.max' => 'Use a number of days from 1 to 3650.',
            'rules.*.date_format' => 'Use a date like 2026-10-28.',
            'rules.*.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    /**
     * The validated rules, normalized: tags lowercased without "#", empty
     * values dropped back to defaults, numbers and booleans cast.
     *
     * @return array<string, mixed>
     */
    public function smartRules(): array
    {
        return SmartFolderRules::normalize($this->cleanRules((array) $this->validated('rules')));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function cleanRules(array $rules): array
    {
        $tags = collect((array) ($rules['tags'] ?? []))
            ->map(fn (mixed $tag) => trim(mb_strtolower((string) $tag), "/# \t"))
            ->filter(fn (string $tag) => $tag !== '')
            ->unique()
            ->values()
            ->all();

        return [
            'tags' => $tags,
            'tags_match' => ($rules['tags_match'] ?? 'all') === 'any' ? 'any' : 'all',
            'folder_id' => isset($rules['folder_id']) && $rules['folder_id'] !== '' ? (int) $rules['folder_id'] : null,
            'edited_within_days' => isset($rules['edited_within_days']) && $rules['edited_within_days'] !== '' ? (int) $rules['edited_within_days'] : null,
            'created_within_days' => isset($rules['created_within_days']) && $rules['created_within_days'] !== '' ? (int) $rules['created_within_days'] : null,
            'edited_from' => ($rules['edited_from'] ?? '') ?: null,
            'edited_to' => ($rules['edited_to'] ?? '') ?: null,
            'created_from' => ($rules['created_from'] ?? '') ?: null,
            'created_to' => ($rules['created_to'] ?? '') ?: null,
            'pinned' => filter_var($rules['pinned'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'has_checklist' => filter_var($rules['has_checklist'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'has_open_tasks' => filter_var($rules['has_open_tasks'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}

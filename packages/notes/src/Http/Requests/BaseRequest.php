<?php

namespace Coleus\Notes\Http\Requests;

use Closure;
use Coleus\Notes\Bases\BaseDefinition;
use Coleus\Notes\Bases\Formula;
use Coleus\Notes\Bases\FormulaError;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BaseRequest extends FormRequest
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
        $readable = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            try {
                Formula::parse($value);
            } catch (FormulaError $error) {
                $fail($error->getMessage());
            }
        };

        return [
            'name' => ['required', 'string', 'max:100', 'not_regex:/[\[\]|#\n]/'],
            'definition' => ['sometimes', 'array'],
            'definition.query' => ['nullable', 'string', 'max:500'],
            'definition.columns' => ['array', 'max:'.BaseDefinition::MAX_COLUMNS],
            'definition.columns.*' => ['string', 'max:191'],
            'definition.formulas' => ['array', 'max:'.BaseDefinition::MAX_FORMULAS],
            'definition.formulas.*.name' => ['required', 'string', 'max:50', 'regex:'.BaseDefinition::FORMULA_NAME, 'distinct:ignore_case'],
            'definition.formulas.*.expression' => ['required', 'string', 'max:'.Formula::MAX_LENGTH, $readable],
            'definition.filters' => ['array', 'max:'.BaseDefinition::MAX_FILTERS],
            'definition.filters.*.column' => ['required', 'string', 'max:191'],
            'definition.filters.*.operator' => ['required', Rule::in(array_keys(BaseDefinition::OPERATORS))],
            'definition.filters.*.value' => ['nullable', 'string', 'max:200'],
            'definition.where' => ['nullable', 'string', 'max:'.Formula::MAX_LENGTH, $readable],
            'definition.sort' => ['array', 'max:'.BaseDefinition::MAX_SORT],
            'definition.sort.*.column' => ['required', 'string', 'max:191'],
            'definition.sort.*.direction' => ['required', 'in:asc,desc'],
            'definition.view' => ['nullable', Rule::in(BaseDefinition::VIEWS)],
            'definition.group_by' => ['nullable', 'string', 'max:191'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the base a name.',
            'name.not_regex' => 'Leave out [ ] | and # so the base can be embedded as ![[Name.base]].',
            'definition.columns.max' => 'Use at most '.BaseDefinition::MAX_COLUMNS.' columns.',
            'definition.formulas.max' => 'Use at most '.BaseDefinition::MAX_FORMULAS.' formulas.',
            'definition.formulas.*.name.required' => 'Name the formula.',
            'definition.formulas.*.name.regex' => 'Use letters, digits and _ in a formula name, starting with a letter.',
            'definition.formulas.*.name.distinct' => 'Two formulas have this name.',
            'definition.formulas.*.expression.required' => 'Write the formula.',
            'definition.filters.max' => 'Use at most '.BaseDefinition::MAX_FILTERS.' filters.',
            'definition.filters.*.column.required' => 'Choose what to filter on.',
            'definition.sort.max' => 'Sort by at most '.BaseDefinition::MAX_SORT.' columns.',
        ];
    }

    /**
     * The validated definition in its stored shape.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return BaseDefinition::normalize((array) $this->validated('definition', BaseDefinition::defaults()));
    }
}

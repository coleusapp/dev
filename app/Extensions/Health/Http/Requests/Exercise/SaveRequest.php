<?php

namespace App\Extensions\Health\Http\Requests\Exercise;

use App\Extensions\Health\Enums\DistanceEnum;
use App\Extensions\Health\Enums\DurationEnum;
use App\Extensions\Health\Enums\WeightEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRequest extends FormRequest
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
            'name' => 'required',
            'description' => 'nullable',
            'has_rep' => 'boolean',
            'has_weight' => 'boolean',
            'has_distance' => 'boolean',
            'has_calorie' => 'boolean',
            'weight_unit' => [
                'required_if:has_weight,true',
                Rule::enum(WeightEnum::class)
            ],
            'distance_unit' => [
                'required_if:has_distance,true',
                Rule::enum(DistanceEnum::class)
            ],
            'has_duration' => 'boolean',
            'duration_unit' => [
                'required_if:has_duration,true',
                Rule::enum(DurationEnum::class)
            ]
        ];
    }
}

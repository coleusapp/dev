<?php

namespace Coleus\Notes\Http\Requests;

use Coleus\Notes\Canvas\JsonCanvas;
use Illuminate\Foundation\Http\FormRequest;

class CanvasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Shape is checked loosely here; Canvas\JsonCanvas::normalize keeps
     * only well-formed nodes and edges.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:100'],
            'data' => ['sometimes', 'array'],
            'data.nodes' => ['array', 'max:'.JsonCanvas::MAX_NODES],
            'data.edges' => ['array', 'max:'.JsonCanvas::MAX_EDGES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Give the canvas a name.',
            'data.nodes.max' => 'A canvas holds at most '.JsonCanvas::MAX_NODES.' cards.',
            'data.edges.max' => 'A canvas holds at most '.JsonCanvas::MAX_EDGES.' connections.',
        ];
    }
}

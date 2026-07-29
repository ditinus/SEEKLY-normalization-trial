<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'connector' => ['required', 'in:mock,csv'],
            'dry_run' => ['nullable', 'boolean'],
            'file' => ['required_if:connector,csv', 'file', 'mimes:csv,txt'],
        ];
    }

    public function isMock(): bool
    {
        return $this->input('connector') === 'mock';
    }

    public function isDryRun(): bool
    {
        return $this->boolean('dry_run');
    }
}

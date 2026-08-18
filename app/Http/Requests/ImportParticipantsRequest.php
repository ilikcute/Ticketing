<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'], // max 10MB
            'event_id' => ['required', 'exists:events,id'],
            'mode' => ['required', Rule::in(['insert_only', 'update_existing'])],
        ];
    }
}

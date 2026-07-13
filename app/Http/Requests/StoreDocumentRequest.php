<?php

namespace App\Http\Requests;

use App\Services\DocumentNumberService;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.create');
    }

    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'exists:document_types,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'doc_number_manual' => ['sometimes', 'boolean'],
            'doc_number' => ['nullable', 'required_if:doc_number_manual,1', 'string', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Manual numbers must be unique across documents.
            if ($this->boolean('doc_number_manual') && filled($this->doc_number)) {
                if (! app(DocumentNumberService::class)->isUnique($this->doc_number)) {
                    $validator->errors()->add('doc_number', 'Nomor dokumen ini sudah dipakai.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'doc_number.required_if' => 'Nomor dokumen wajib diisi bila input manual diaktifkan.',
            'title.required' => 'Judul dokumen wajib diisi.',
        ];
    }
}

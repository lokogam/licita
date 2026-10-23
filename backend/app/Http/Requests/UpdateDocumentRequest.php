<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Http\Request;

/**
 * Validation for editing an existing document; the file is optional (keeps the current one).
 */
final class UpdateDocumentRequest extends FormRequest
{
    protected function rules(Request $request): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:200'],
            'file' => ['file', 'mimes:pdf,zip', 'max_kb:' . UpdateTenderRequest::MAX_DOCUMENT_KB],
        ];
    }

    protected function attributes(): array
    {
        return [
            'title' => 'título del documento',
            'description' => 'descripción del documento',
            'file' => 'documento',
        ];
    }
}

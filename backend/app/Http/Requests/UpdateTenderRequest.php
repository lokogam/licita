<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Http\Request;
use App\Models\TenderDocument;
use App\Services\Security\VirusScanner;
use App\Validation\Rules\Clean;
use App\Validation\Validator;

/**
 * Validation for editing a tender: same rules as creation plus the documents section.
 */
final class UpdateTenderRequest extends StoreTenderRequest
{
    public const MAX_DOCUMENT_KB = 10240;

    public function __construct(private readonly VirusScanner $scanner)
    {
    }

    protected function rules(Request $request): array
    {
        return parent::rules($request) + [
            'documents' => ['array'],
            'documents.*.title' => ['required', 'string', 'max:100'],
            'documents.*.description' => ['required', 'string', 'max:200'],
            'documents.*.file' => [
                'required',
                'file',
                'mimes:pdf,zip',
                'max_kb:' . self::MAX_DOCUMENT_KB,
                new Clean($this->scanner),
            ],
        ];
    }

    protected function attributes(): array
    {
        return parent::attributes() + [
            'documents' => 'documentos',
            'documents.*.title' => 'título del documento',
            'documents.*.description' => 'descripción del documento',
            'documents.*.file' => 'documento',
        ];
    }

    /**
     * Besides the schedule rules, the tender must end up with at least one document.
     */
    protected function after(Validator $validator, array $data, Request $request): void
    {
        parent::after($validator, $data, $request);

        $existing = TenderDocument::query()->where('tender_id', (int) $request->route('id'))->count();
        $new = is_array($data['documents'] ?? null) ? count($data['documents']) : 0;

        if ($existing + $new < 1) {
            $validator->addError('documents', 'Debe registrar al menos 1 documento.');
        }
    }
}

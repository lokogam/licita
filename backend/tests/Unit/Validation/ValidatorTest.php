<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Core\Http\UploadedFile;
use App\Validation\Rules\Exists;
use App\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_it_passes_valid_data_and_returns_only_validated_fields(): void
    {
        $validator = new Validator(
            ['subject' => 'Compra de equipos', 'extra' => 'ignored'],
            ['subject' => ['required', 'string', 'max:150']],
        );

        $this->assertFalse($validator->fails());
        $this->assertSame(['subject' => 'Compra de equipos'], $validator->validated());
    }

    public function test_required_rejects_missing_and_empty_values(): void
    {
        $validator = new Validator(
            ['description' => ''],
            ['subject' => ['required'], 'description' => ['required']],
            ['subject' => 'objeto', 'description' => 'descripción'],
        );

        $this->assertSame([
            'subject' => ['El campo objeto es obligatorio.'],
            'description' => ['El campo descripción es obligatorio.'],
        ], $validator->errors());
    }

    public function test_required_rejects_whitespace_only_strings(): void
    {
        $validator = new Validator(['subject' => "   \t "], ['subject' => ['required']], ['subject' => 'objeto']);

        $this->assertSame(['subject' => ['El campo objeto no puede contener solo espacios.']], $validator->errors());
    }

    public function test_it_reports_only_the_first_failing_rule_per_field(): void
    {
        $validator = new Validator(['subject' => ''], ['subject' => ['required', 'max:3']]);

        $this->assertCount(1, $validator->errors()['subject']);
    }

    public function test_optional_fields_skip_rules_when_absent(): void
    {
        $validator = new Validator([], ['subject' => ['string', 'max:3']]);

        $this->assertFalse($validator->fails());
    }

    public function test_max_counts_multibyte_characters(): void
    {
        $this->assertFalse((new Validator(['s' => 'ñññ'], ['s' => ['max:3']]))->fails());
        $this->assertTrue((new Validator(['s' => 'ññññ'], ['s' => ['max:3']]))->fails());
    }

    /**
     * @return iterable<string, array{string, mixed, bool}>
     */
    public static function ruleCases(): iterable
    {
        yield 'in accepts listed value' => ['in:COP,USD,EUR', 'USD', true];
        yield 'in rejects other value' => ['in:COP,USD,EUR', 'MXN', false];
        yield 'in is case sensitive' => ['in:COP,USD,EUR', 'usd', false];
        yield 'decimal accepts integer' => ['decimal:2', '1500', true];
        yield 'decimal accepts two decimals' => ['decimal:2', '1500.25', true];
        yield 'decimal accepts numeric types' => ['decimal:2', 99.5, true];
        yield 'decimal rejects three decimals' => ['decimal:2', '1.123', false];
        yield 'decimal rejects letters' => ['decimal:2', '12a', false];
        yield 'decimal rejects comma' => ['decimal:2', '12,50', false];
        yield 'decimal rejects negative sign' => ['decimal:2', '-5', false];
        yield 'decimal rejects exponent' => ['decimal:2', '1e5', false];
        yield 'gt accepts bigger value' => ['gt:0', '0.01', true];
        yield 'gt rejects zero' => ['gt:0', '0', false];
        yield 'max_value accepts limit' => ['max_value:9999999999999.99', '9999999999999.99', true];
        yield 'max_value rejects overflow' => ['max_value:9999999999999.99', '99999999999999', false];
        yield 'date accepts valid date' => ['date_format:Y-m-d', '2026-02-28', true];
        yield 'date rejects impossible date' => ['date_format:Y-m-d', '2026-02-30', false];
        yield 'date rejects other format' => ['date_format:Y-m-d', '28/02/2026', false];
        yield 'time accepts 24h time' => ['date_format:H:i', '23:59', true];
        yield 'time rejects invalid hour' => ['date_format:H:i', '24:00', false];
        yield 'time rejects seconds' => ['date_format:H:i', '10:00:00', false];
        yield 'integer accepts digits' => ['integer', '15', true];
        yield 'integer rejects decimals' => ['integer', '1.5', false];
        yield 'string rejects arrays' => ['string', ['a'], false];
        yield 'array accepts arrays' => ['array', ['a'], true];
        yield 'min_items rejects short arrays' => ['min_items:1', [], false];
    }

    #[DataProvider('ruleCases')]
    public function test_rules(string $rule, mixed $value, bool $passes): void
    {
        $validator = new Validator(['field' => $value], ['field' => [$rule]]);

        $this->assertSame($passes, !$validator->fails());
    }

    public function test_exists_rule_uses_the_given_lookup(): void
    {
        $exists = new Exists(fn (mixed $value): bool => $value === '7', 'actividad');

        $this->assertFalse((new Validator(['activity_id' => '7'], ['activity_id' => [$exists]]))->fails());
        $this->assertSame(
            ['activity_id' => ['La actividad seleccionada no existe.']],
            (new Validator(['activity_id' => '8'], ['activity_id' => [$exists]]))->errors(),
        );
    }

    public function test_wildcard_rules_validate_each_array_item(): void
    {
        $validator = new Validator(
            ['documents' => [['title' => 'Pliego'], ['title' => '']]],
            ['documents' => ['array'], 'documents.*.title' => ['required', 'max:100']],
            ['documents.*.title' => 'título del documento'],
        );

        $this->assertSame(
            ['documents.1.title' => ['El campo título del documento es obligatorio.']],
            $validator->errors(),
        );
    }

    public function test_file_rules_check_real_mime_type_extension_and_size(): void
    {
        $pdf = $this->tempFile("%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $fake = $this->tempFile('MZ this is an executable pretending to be a pdf');

        $rules = ['file' => ['file', 'mimes:pdf,zip', 'max_kb:1']];

        $valid = new UploadedFile($pdf, 'pliego.pdf', 'application/pdf', filesize($pdf), UPLOAD_ERR_OK);
        $spoofed = new UploadedFile($fake, 'virus.pdf', 'application/pdf', filesize($fake), UPLOAD_ERR_OK);
        $wrongExtension = new UploadedFile($pdf, 'pliego.exe', 'application/pdf', filesize($pdf), UPLOAD_ERR_OK);
        $tooBig = new UploadedFile($pdf, 'pliego.pdf', 'application/pdf', 2048, UPLOAD_ERR_OK);
        $failedUpload = new UploadedFile('', 'x.pdf', '', 0, UPLOAD_ERR_PARTIAL);

        $this->assertFalse((new Validator(['file' => $valid], $rules))->fails());
        $this->assertTrue((new Validator(['file' => $spoofed], $rules))->fails());
        $this->assertTrue((new Validator(['file' => $wrongExtension], $rules))->fails());
        $this->assertTrue((new Validator(['file' => $tooBig], $rules))->fails());
        $this->assertTrue((new Validator(['file' => $failedUpload], $rules))->fails());
        $this->assertTrue((new Validator(['file' => 'not a file'], $rules))->fails());
    }

    public function test_after_hooks_can_add_cross_field_errors(): void
    {
        $validator = new Validator(['start' => '10:00', 'end' => '09:00'], ['start' => ['required'], 'end' => ['required']]);
        $validator->after(function (Validator $v, array $data): void {
            if ($data['end'] <= $data['start']) {
                $v->addError('end', 'La hora de cierre debe ser mayor a la hora de inicio.');
            }
        });

        $this->assertSame(['end' => ['La hora de cierre debe ser mayor a la hora de inicio.']], $validator->errors());
    }

    public function test_after_hooks_do_not_run_when_basic_rules_fail(): void
    {
        $ran = false;
        $validator = new Validator([], ['start' => ['required']]);
        $validator->after(function () use (&$ran): void {
            $ran = true;
        });

        $validator->fails();

        $this->assertFalse($ran);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'val');
        file_put_contents($path, $contents);

        return $path;
    }
}

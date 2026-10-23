<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * UNSPSC classifier entry (segment > family > class > product).
 *
 * @property int $id
 * @property int $segment_code
 * @property string $segment
 * @property int $family_code
 * @property string $family
 * @property int $class_code
 * @property string $class_name
 * @property int $product_code
 * @property string $product
 */
final class Activity extends Model
{
    public $timestamps = false;

    protected $table = 'activities';

    protected $fillable = [
        'segment_code',
        'segment',
        'family_code',
        'family',
        'class_code',
        'class_name',
        'product_code',
        'product',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'segment_code' => 'integer',
        'family_code' => 'integer',
        'class_code' => 'integer',
        'product_code' => 'integer',
    ];
}

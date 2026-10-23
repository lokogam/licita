<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;

/**
 * JSON representation of an activity.
 */
final class ActivityResource
{
    /**
     * @return array{id: int, product_code: int, product: string, class_name: string, family: string, segment: string}
     */
    public static function make(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'product_code' => $activity->product_code,
            'product' => $activity->product,
            'class_name' => $activity->class_name,
            'family' => $activity->family,
            'segment' => $activity->segment,
        ];
    }
}

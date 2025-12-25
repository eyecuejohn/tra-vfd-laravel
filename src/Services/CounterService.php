<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Services;

use Eyecuejohn\TraVfd\Models\TraVfdCounter;
use Illuminate\Support\Carbon;

class CounterService
{
    /**
     * Generate the next sequential counters and format the Z-Number.
     *
     * @return array
     */
    public function getNextFiscalSequence(): array
    {
        $tin = config('tra-vfd.tin');
        $today = Carbon::now()->format('Ymd');

        // Find existing counter for this TIN or create a new starting point
        $counter = TraVfdCounter::firstOrCreate(
            ['tin' => $tin],
            [
                'gc' => 0,
                'rct_num' => 0,
                'z_num_count' => 1,
                'last_z_date' => $today
            ]
        );

        // DAILY Z-REPORT LOGIC:
        // If the stored date is not today, increment the Z-counter and update date
        if ($counter->last_z_date !== $today) {
            $counter->z_num_count += 1;
            $counter->last_z_date = $today;
        }

        // INCREMENT FISCAL COUNTERS:
        $counter->gc += 1;
        $counter->rct_num += 1;
        $counter->save();

        return [
            'gc'      => $counter->gc,
            'rctnum'  => $counter->rct_num,
            'znum'    => "{$counter->z_num_count}/{$today}"
        ];
    }
}
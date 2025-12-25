<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Models;

use Illuminate\Database\Eloquent\Model;

class TraVfdCounter extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tra_vfd_counters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tin',           // Taxpayer Identification Number 
        'gc',            // Global Counter [cite: 34]
        'rct_num',       // Receipt Number [cite: 16, 33]
        'z_num_count',   // The daily increment (e.g., the "2") 
        'last_z_date'    // The date tracking (e.g., "20250922") 
    ];
}
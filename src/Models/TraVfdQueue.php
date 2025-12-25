<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Models;

use Illuminate\Database\Eloquent\Model;

class TraVfdQueue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tra_vfd_queue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tin', 
        'rct_num', 
        'xml_payload', 
        'status', 
        'attempts', 
        'last_error'
    ];

    /**
     * Scope a query to only include pending queue items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
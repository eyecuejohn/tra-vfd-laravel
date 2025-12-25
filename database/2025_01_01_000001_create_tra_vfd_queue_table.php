<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table acts as a fallback storage for receipts when the TRA API
     * is unreachable or returns a retryable error.
     */
    public function up()
    {
        Schema::create('tra_vfd_queue', function (Blueprint $table) {
            $table->id();
            
            // Link to the specific TIN and receipt number for tracking
            $table->string('tin');
            $table->string('rct_num');
            
            // The complete signed XML payload ready for transmission
            $table->longText('xml_payload');
            
            // Queue state management
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            
            // Error logging for debugging
            $table->text('last_error')->nullable();
            
            // Indexing for faster retrieval of pending jobs
            $table->index(['status', 'attempts']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('tra_vfd_queue');
    }
};
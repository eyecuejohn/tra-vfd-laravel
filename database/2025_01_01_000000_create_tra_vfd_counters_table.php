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

return new class extends Migration {
    public function up()
    {
        Schema::create('tra_vfd_counters', function (Blueprint $table) {
            $table->id();
            $table->string('tin')->unique(); // Unique counter per Business TIN
            $table->unsignedBigInteger('gc')->default(0); // Global Counter
            $table->unsignedBigInteger('rct_num')->default(0); // Receipt Number
            $table->integer('z_num_count')->default(1); // The daily increment (e.g., 2)
            $table->string('last_z_date'); // The date tracking (e.g., 20250922)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tra_vfd_counters');
    }
};
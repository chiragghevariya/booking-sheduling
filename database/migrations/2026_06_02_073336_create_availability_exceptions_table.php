<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->date('date');
            // When start_time/end_time are null, the whole date is blocked off.
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('type', ['blocked', 'custom'])->default('blocked');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_exceptions');
    }
};

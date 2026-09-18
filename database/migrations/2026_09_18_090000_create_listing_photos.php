<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_photos', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('status', 16)->default('uploading');
            $table->string('storage_prefix')->unique();
            $table->char('source_hash', 64);
            $table->unsignedInteger('source_bytes');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->jsonb('variants')->default('{}');
            $table->timestampTz('dispatched_at')->nullable();
            $table->timestampsTz();
            $table->index(['listing_id', 'position', 'id']);
            $table->index(['status', 'updated_at', 'id']);
        });
        DB::statement('ALTER TABLE listing_photos ADD CONSTRAINT listing_photos_position_valid CHECK (position BETWEEN 0 AND 7)');
        DB::statement("ALTER TABLE listing_photos ADD CONSTRAINT listing_photos_status_valid CHECK (status IN ('uploading','queued','ready','failed'))");
        DB::statement("ALTER TABLE listings ALTER COLUMN last_step SET DEFAULT 'photos'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE listings ALTER COLUMN last_step SET DEFAULT 'details'");
        Schema::dropIfExists('listing_photos');
    }
};

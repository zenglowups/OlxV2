<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary();
            $table->string('name_ro', 100);
            $table->string('name_en', 100);
        });
        Schema::create('counties', function (Blueprint $table) {
            $table->id();
            $table->char('country_code', 2);
            $table->foreign('country_code')->references('code')->on('countries')->restrictOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->unique(['country_code', 'code']);
            $table->unique(['id', 'country_code']);
        });
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('creation_token');
            $table->unique(['user_id', 'creation_token']);
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 140)->default('');
            $table->text('description')->default('');
            $table->jsonb('attributes')->default('{}');
            $table->string('pricing_mode', 20)->default('fixed');
            $table->bigInteger('price_minor')->nullable();
            $table->char('currency', 3)->default('RON');
            $table->char('country_code', 2)->default('RO');
            $table->foreign('country_code')->references('code')->on('countries')->restrictOnDelete();
            $table->unsignedBigInteger('county_id')->nullable();
            $table->foreign(['county_id', 'country_code'])->references(['id', 'country_code'])->on('counties')->restrictOnDelete();
            $table->string('locality', 100)->nullable();
            $table->string('contact_method', 10)->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('moderation_state', 20)->default('unreviewed');
            $table->unsignedInteger('version')->default(1);
            $table->string('last_step', 20)->default('details');
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
            $table->index(['user_id', 'status', 'updated_at', 'id']);
            $table->index(['category_id', 'status']);
        });
        DB::statement('ALTER TABLE listings ADD CONSTRAINT listings_price_valid CHECK (price_minor IS NULL OR price_minor BETWEEN 0 AND 99999999999)');
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_pricing_valid CHECK (pricing_mode IN ('fixed','negotiable','free','exchange','contact'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_currency_valid CHECK (currency IN ('RON','EUR'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_contact_valid CHECK (contact_method IS NULL OR contact_method IN ('phone','email'))");
        DB::statement("ALTER TABLE listings ADD CONSTRAINT listings_status_valid CHECK (status IN ('draft','active','reserved','sold','expired','suspended','removed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
        Schema::dropIfExists('counties');
        Schema::dropIfExists('countries');
    }
};

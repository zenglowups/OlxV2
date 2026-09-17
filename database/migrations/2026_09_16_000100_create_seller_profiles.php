<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username', 32)->unique();
            $table->text('description')->nullable();
            $table->string('location_label', 100)->nullable();
            $table->string('business_name', 160)->nullable();
            $table->text('tax_id')->nullable();
            $table->boolean('show_tax_id')->default(false);
            $table->text('phone')->nullable();
            $table->string('contact_method', 10)->default('email');
            $table->boolean('is_public')->default(false);
            $table->uuid('avatar_id')->nullable()->unique();
            $table->string('avatar_path')->nullable();
            $table->timestampsTz();
        });
        DB::statement('CREATE UNIQUE INDEX seller_profiles_username_lower ON seller_profiles (lower(username))');
        DB::statement("ALTER TABLE seller_profiles ADD CONSTRAINT seller_profiles_contact_method_check CHECK (contact_method IN ('email', 'phone'))");
        Schema::create('pending_file_deletions', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('s3');
            $table->string('path')->unique();
            $table->timestampTz('available_at')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_file_deletions');
        Schema::dropIfExists('seller_profiles');
    }
};

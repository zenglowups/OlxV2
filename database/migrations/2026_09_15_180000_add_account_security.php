<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type', 20)->default('individual');
            $table->string('locale', 2)->default('ro');
            $table->string('status', 20)->default('active')->index();
            $table->timestampTz('suspended_until')->nullable();
            $table->unsignedInteger('auth_version')->default(1);
        });
        DB::statement('CREATE UNIQUE INDEX users_email_case_insensitive ON users (lower(email))');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_account_type_check CHECK (account_type IN ('individual', 'business'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active', 'suspended', 'banned'))");
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('provider_id');
            $table->timestampsTz();
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        DB::statement('DROP INDEX users_email_case_insensitive');
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_account_type_check, DROP CONSTRAINT users_status_check');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['account_type', 'locale', 'status', 'suspended_until', 'auth_version']));
    }
};

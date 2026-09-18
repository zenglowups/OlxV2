<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestampTz('renewed_at')->nullable();
            $table->timestampTz('removed_at')->nullable();
        });
        Schema::create('listing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->unsignedInteger('version');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['listing_id', 'created_at', 'id']);
        });
        DB::statement("CREATE INDEX listings_public_order ON listings (published_at DESC, id DESC) WHERE status IN ('active','reserved')");
        DB::statement("CREATE INDEX listings_expiry_due ON listings (expires_at, id) WHERE status IN ('active','reserved')");
        DB::statement("CREATE INDEX listings_category_public_order ON listings (category_id, published_at DESC, id DESC) WHERE status IN ('active','reserved')");
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_events');
        DB::statement('DROP INDEX listings_public_order');
        DB::statement('DROP INDEX listings_expiry_due');
        DB::statement('DROP INDEX listings_category_public_order');
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn(['renewed_at', 'removed_at']));
    }
};

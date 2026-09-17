<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('slug', 100)->unique();
            $table->string('name_ro', 100);
            $table->string('name_en', 100);
            $table->text('description_ro')->nullable();
            $table->text('description_en')->nullable();
            $table->string('icon', 24)->default('grid');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestampsTz();
            $table->index(['parent_id', 'is_active', 'position', 'id']);
        });
        DB::statement('ALTER TABLE categories ADD CONSTRAINT category_not_own_parent CHECK (parent_id IS DISTINCT FROM id)');
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('key', 48);
            $table->string('label_ro', 100);
            $table->string('label_en', 100);
            $table->string('type', 12);
            $table->string('unit', 20)->nullable();
            $table->jsonb('options')->nullable();
            $table->decimal('minimum', 14, 2)->nullable();
            $table->decimal('maximum', 14, 2)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestampsTz();
            $table->unique(['category_id', 'key']);
            $table->index(['category_id', 'is_active', 'position']);
        });
        DB::statement("ALTER TABLE category_attributes ADD CONSTRAINT attribute_type_check CHECK (type IN ('text','number','select','boolean'))");
        DB::statement('ALTER TABLE category_attributes ADD CONSTRAINT attribute_range_check CHECK (minimum IS NULL OR maximum IS NULL OR minimum <= maximum)');
        Schema::create('admin_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            $table->jsonb('changes');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audits');
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('categories');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};

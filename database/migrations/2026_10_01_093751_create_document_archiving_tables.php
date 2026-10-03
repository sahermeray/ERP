<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('archive_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_location_id')->nullable()->constrained('archive_locations')->nullOnDelete();
            $table->string('code', 64);
            $table->string('name');
            $table->string('location_type', 24)->default('shelf');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('document_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->unique()->constrained()->restrictOnDelete();
            $table->string('archive_number', 96)->unique();
            $table->foreignId('archive_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->useCurrent();
            $table->date('retention_until')->nullable();
            $table->string('disposition_status', 24)->default('retained');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['disposition_status', 'retention_until']);
        });

        Schema::create('document_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 48);
            $table->string('previous_status', 24)->nullable();
            $table->string('new_status', 24)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['document_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_activity');
        Schema::dropIfExists('document_archives');
        Schema::dropIfExists('archive_locations');
    }
};

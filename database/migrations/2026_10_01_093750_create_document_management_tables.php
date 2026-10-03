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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('retention_period_months')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('sending_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number', 64);
            $table->string('direction', 16);
            $table->string('status', 24)->default('registered');
            $table->string('priority', 16)->default('normal');
            $table->string('confidentiality', 24)->default('internal');
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->date('document_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('external_reference', 128)->nullable();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'direction', 'reference_number']);
            $table->index(['organization_id', 'direction', 'status']);
            $table->index(['organization_id', 'document_date']);
            $table->index(['organization_id', 'document_type_id', 'document_date']);
            $table->index(['organization_id', 'recipient_department_id', 'document_date']);
            $table->index(['organization_id', 'sending_department_id', 'document_date']);
            $table->index(['organization_id', 'subject']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('document_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('party_type', 16);
            $table->string('organization_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('postal_address')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['document_id', 'party_type']);
        });

        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->index(['document_id', 'is_primary']);
        });

        Schema::create('document_file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_attachment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('storage_disk', 64)->default('private');
            $table->text('storage_path');
            $table->text('original_file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['document_attachment_id', 'version_number']);
            $table->index('sha256');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_file_versions');
        Schema::dropIfExists('document_attachments');
        Schema::dropIfExists('document_parties');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_types');
    }
};

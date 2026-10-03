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
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->text('original_file_name')->nullable();
            $table->string('storage_disk', 64)->default('local');
            $table->text('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_attachments', function (Blueprint $table) {
            $table->dropColumn([
                'original_file_name',
                'storage_disk',
                'storage_path',
                'mime_type',
                'size_bytes',
            ]);
        });
    }
};

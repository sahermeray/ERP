<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('the document management modules have their core tables', function () {
    foreach ([
        'organizations',
        'departments',
        'organization_user',
        'roles',
        'permissions',
        'role_permission',
        'organization_user_role',
        'document_types',
        'documents',
        'document_parties',
        'document_attachments',
        'document_file_versions',
        'archive_locations',
        'document_archives',
        'document_activity',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(Schema::hasColumns('users', ['phone', 'is_active', 'last_login_at']))->toBeTrue();
    expect(Schema::hasColumns('documents', [
        'reference_number',
        'document_date',
        'recipient_department_id',
        'sending_department_id',
        'document_type_id',
        'priority',
        'notes',
        'created_by',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
    expect(Schema::hasColumns('document_file_versions', ['storage_path']))->toBeTrue();
    expect(Schema::hasColumns('document_archives', ['document_id', 'archive_location_id', 'retention_until']))->toBeTrue();
});

test('reference numbers are unique per organization and correspondence type', function () {
    $organizationIds = [];

    foreach (['north-office', 'south-office'] as $slug) {
        $organizationIds[] = DB::table('organizations')->insertGetId([
            'name' => $slug,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $userId = DB::table('users')->insertGetId([
        'name' => 'Records Officer',
        'email' => 'records@example.test',
        'password' => 'hashed-password',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $documentIds = [];

    foreach ($organizationIds as $organizationId) {
        $documentIds[] = DB::table('documents')->insertGetId([
            'organization_id' => $organizationId,
            'created_by' => $userId,
            'reference_number' => '2026-0001',
            'direction' => 'incoming',
            'subject' => 'Annual report',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    DB::table('documents')->insert([
        'organization_id' => $organizationIds[0],
        'created_by' => $userId,
        'reference_number' => '2026-0001',
        'direction' => 'outgoing',
        'subject' => 'Annual report response',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('document_archives')->insert([
        'document_id' => $documentIds[0],
        'archive_number' => 'ARC-2026-0001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('documents')->insert([
        'organization_id' => $organizationIds[0],
        'created_by' => $userId,
        'reference_number' => '2026-0001',
        'direction' => 'incoming',
        'subject' => 'Duplicate number',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('documents')->where('id', $documentIds[0])->delete())
        ->toThrow(QueryException::class);
});

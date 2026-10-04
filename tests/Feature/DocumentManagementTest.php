<?php

use App\Models\Document;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createOrganizationForDocumentTests(string $name): int
{
    return DB::table('organizations')->insertGetId([
        'name' => $name,
        'slug' => str()->slug($name).'-'.str()->random(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function assignDocumentTestUserToOrganization(User $user, int $organizationId): void
{
    DB::table('organization_user')->insert([
        'organization_id' => $organizationId,
        'user_id' => $user->id,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('protects document management pages and renders the authenticated workspace', function () {
    $this->get('/documents/incoming')->assertRedirect(route('login'));
    $this->get('/documents/outgoing')->assertRedirect(route('login'));
    $this->get('/archive')->assertRedirect(route('login'));

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Public Records Office');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Document Management')
        ->assertSee('Incoming Documents')
        ->assertSee('Outgoing Documents')
        ->assertSee('Archive');

    $this->get(route('documents.create', 'incoming'))
        ->assertOk()
        ->assertSee('Add Incoming Document')
        ->assertSee('Sender');
    $this->get(route('documents.create', 'outgoing'))
        ->assertOk()
        ->assertSee('Add Outgoing Document')
        ->assertSee('Recipient');
    $this->get(route('documents.archive'))->assertOk()->assertSee('Archive');
});

it('seeds an active development organization membership idempotently', function () {
    $user = User::factory()->create(['email' => 'development-user@example.com']);
    config(['app.development_user_email' => $user->email]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect($user->fresh()->currentOrganization()?->slug)->toBe('development-office');
    $this->assertDatabaseCount('organizations', 1);
    $this->assertDatabaseHas('organization_user', [
        'user_id' => $user->id,
        'status' => 'active',
    ]);
});

it('stores documents in the selected register with server-controlled direction and party details', function () {
    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Central Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);
    $departmentId = DB::table('departments')->insertGetId([
        'organization_id' => $organizationId,
        'name' => 'Administration',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->post(route('documents.store', 'incoming'), [
        'reference_number' => 'IN-2026-0001',
        'document_date' => '2026-10-02',
        'subject' => 'Annual service report',
        'party_name' => 'Department of Transport',
        'recipient_department_id' => $departmentId,
        'priority' => 'high',
        'status' => 'registered',
        'direction' => 'outgoing',
    ])->assertRedirect();

    $incoming = Document::query()->where('reference_number', 'IN-2026-0001')->firstOrFail();
    expect($incoming->direction)->toBe('incoming')
        ->and($incoming->organization_id)->toBe($organizationId)
        ->and($incoming->created_by)->toBe($user->id);
    $this->assertDatabaseHas('document_parties', [
        'document_id' => $incoming->id,
        'party_type' => 'sender',
        'organization_name' => 'Department of Transport',
    ]);

    $this->put(route('documents.update', $incoming), [
        'reference_number' => 'IN-2026-0001',
        'document_date' => '2026-10-02',
        'subject' => 'Annual service report revised',
        'party_name' => 'Ministry of Transport',
        'recipient_department_id' => $departmentId,
        'priority' => 'high',
        'status' => 'under_review',
        'direction' => 'outgoing',
    ])->assertRedirect(route('documents.show', $incoming));

    expect($incoming->fresh()->direction)->toBe('incoming')
        ->and($incoming->fresh()->subject)->toBe('Annual service report revised');
    $this->assertDatabaseHas('document_parties', [
        'document_id' => $incoming->id,
        'party_type' => 'sender',
        'organization_name' => 'Ministry of Transport',
    ]);

    $this->post(route('documents.store', 'outgoing'), [
        'reference_number' => 'OUT-2026-0001',
        'subject' => 'Response to annual report',
        'party_name' => 'Department of Transport',
        'sending_department_id' => $departmentId,
        'priority' => 'normal',
        'status' => 'registered',
    ])->assertRedirect();

    $outgoing = Document::query()->where('reference_number', 'OUT-2026-0001')->firstOrFail();
    expect($outgoing->direction)->toBe('outgoing');
    $this->assertDatabaseHas('document_parties', [
        'document_id' => $outgoing->id,
        'party_type' => 'recipient',
        'organization_name' => 'Department of Transport',
    ]);
    $this->assertDatabaseCount('documents', 2);
    $this->assertDatabaseCount('document_attachments', 0);
    $this->get(route('documents.show', $incoming))->assertOk();
    $this->get(route('documents.show', $outgoing))->assertOk();
});

it('translates stored document status and direction labels without changing their values', function () {
    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Localization Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $this->actingAs($user)
        ->post(route('locale.update'), ['locale' => 'ar'])
        ->assertRedirect();

    $this->post(route('documents.store', 'incoming'), [
        'reference_number' => 'IN-2026-LOC-001',
        'subject' => 'Localization test document',
        'priority' => 'normal',
        'status' => 'closed',
    ])->assertRedirect();

    $document = Document::query()->where('reference_number', 'IN-2026-LOC-001')->firstOrFail();
    $this->assertDatabaseHas('documents', [
        'id' => $document->id,
        'direction' => 'incoming',
        'status' => 'closed',
    ]);

    $this->get(route('documents.show', $document))
        ->assertOk()
        ->assertSee('وارد')
        ->assertSee('مغلق')
        ->assertSee('<html lang="ar" dir="rtl">', false);

    $this->get(route('documents.archive'))
        ->assertOk()
        ->assertSee($document->reference_number)
        ->assertSee('مغلق');
});

it('stores multiple private attachments on a document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Attachment Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $response = $this->actingAs($user)->post(route('documents.store', 'incoming'), [
        'reference_number' => 'IN-2026-ATT-001',
        'subject' => 'Document with supporting files',
        'priority' => 'normal',
        'status' => 'registered',
        'attachments' => [
            UploadedFile::fake()->create('brief.pdf', 30, 'application/pdf'),
            UploadedFile::fake()->image('scan.png'),
        ],
    ]);

    $documentId = Document::query()->where('reference_number', 'IN-2026-ATT-001')->value('id');
    $attachments = DB::table('document_attachments')->where('document_id', $documentId)->get();

    $response->assertRedirect(route('documents.show', $documentId));
    expect($attachments)->toHaveCount(2);
    foreach ($attachments as $attachment) {
        Storage::disk('local')->assertExists($attachment->storage_path);
    }

    $pdfAttachment = $attachments->firstWhere('original_file_name', 'brief.pdf');
    expect($pdfAttachment->mime_type)->toBe('application/pdf')
        ->and($pdfAttachment->size_bytes)->toBeGreaterThan(0)
        ->and($pdfAttachment->uploaded_by)->toBe($user->id);
    $this->get(route('documents.show', $documentId))
        ->assertOk()
        ->assertSee('brief.pdf')
        ->assertSee('View / Open')
        ->assertSee('Download');
    $this->get(route('documents.attachments.open', [$documentId, $pdfAttachment->id]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
    $this->get(route('documents.attachments.download', [$documentId, $pdfAttachment->id]))
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename=brief.pdf');

    auth()->logout();
    $this->get(route('documents.attachments.open', [$documentId, $pdfAttachment->id]))
        ->assertRedirect(route('login'));

    $otherUser = User::factory()->create();
    $otherOrganizationId = createOrganizationForDocumentTests('Other Attachment Registry');
    assignDocumentTestUserToOrganization($otherUser, $otherOrganizationId);
    $this->actingAs($otherUser)
        ->get(route('documents.attachments.open', [$documentId, $pdfAttachment->id]))
        ->assertNotFound();
});

it('stores and displays an image attachment on an outgoing document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Outgoing Image Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $response = $this->actingAs($user)->post(route('documents.store', 'outgoing'), [
        'reference_number' => 'OUT-2026-IMG-001',
        'subject' => 'Outgoing document with image attachment',
        'priority' => 'normal',
        'status' => 'registered',
        'attachments' => [UploadedFile::fake()->image('signed-page.jpg')],
    ]);

    $document = Document::query()->where('reference_number', 'OUT-2026-IMG-001')->firstOrFail();
    $attachment = $document->attachments()->firstOrFail();

    $response->assertRedirect(route('documents.show', $document));
    expect($attachment->original_file_name)->toBe('signed-page.jpg')
        ->and($attachment->mime_type)->toBe('image/jpeg');
    Storage::disk('local')->assertExists($attachment->storage_path);

    $this->get(route('documents.show', $document))
        ->assertOk()
        ->assertSee('signed-page.jpg')
        ->assertSee('image/jpeg');
});

it('rejects unsupported attachment types without creating the document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Validated Attachments Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $this->actingAs($user)
        ->from(route('documents.create', 'outgoing'))
        ->post(route('documents.store', 'outgoing'), [
            'reference_number' => 'OUT-2026-BAD-FILE',
            'subject' => 'Unsupported file submission',
            'priority' => 'normal',
            'status' => 'registered',
            'attachments' => [UploadedFile::fake()->create('payload.txt', 2, 'text/plain')],
        ])
        ->assertRedirect(route('documents.create', 'outgoing'))
        ->assertSessionHasErrors('attachments.0');

    $this->assertDatabaseMissing('documents', ['reference_number' => 'OUT-2026-BAD-FILE']);
    $this->assertDatabaseCount('document_attachments', 0);
});

it('rejects attachments larger than ten megabytes', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Attachment Size Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $this->actingAs($user)
        ->from(route('documents.create', 'incoming'))
        ->post(route('documents.store', 'incoming'), [
            'reference_number' => 'IN-2026-LARGE-FILE',
            'subject' => 'Oversized attachment submission',
            'priority' => 'normal',
            'status' => 'registered',
            'attachments' => [UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')],
        ])
        ->assertRedirect(route('documents.create', 'incoming'))
        ->assertSessionHasErrors('attachments.0');

    $this->assertDatabaseMissing('documents', ['reference_number' => 'IN-2026-LARGE-FILE']);
    $this->assertDatabaseCount('document_attachments', 0);
});

it('shows closed incoming and outgoing documents with their existing attachments and filters them by status', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Archived Attachments Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);
    $incomingDepartmentId = DB::table('departments')->insertGetId([
        'organization_id' => $organizationId,
        'name' => 'Incoming Office',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $outgoingDepartmentId = DB::table('departments')->insertGetId([
        'organization_id' => $organizationId,
        'name' => 'Outgoing Office',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)->post(route('documents.store', 'incoming'), [
        'reference_number' => 'IN-2026-ARCHIVE-ATT',
        'subject' => 'Archived document with attachment',
        'document_date' => '2026-10-01',
        'recipient_department_id' => $incomingDepartmentId,
        'notes' => 'Incoming archive notes',
        'priority' => 'normal',
        'status' => 'registered',
        'attachments' => [UploadedFile::fake()->image('archive-scan.jpg')],
    ])->assertRedirect();

    $this->actingAs($user)->post(route('documents.store', 'outgoing'), [
        'reference_number' => 'OUT-2026-ARCHIVE-ATT',
        'subject' => 'Outgoing document with attachment',
        'document_date' => '2026-10-03',
        'sending_department_id' => $outgoingDepartmentId,
        'notes' => 'Outgoing archive notes',
        'priority' => 'normal',
        'status' => 'registered',
        'attachments' => [UploadedFile::fake()->create('outgoing.pdf', 20, 'application/pdf')],
    ])->assertRedirect();

    $incoming = Document::query()->where('reference_number', 'IN-2026-ARCHIVE-ATT')->firstOrFail();
    $outgoing = Document::query()->where('reference_number', 'OUT-2026-ARCHIVE-ATT')->firstOrFail();
    $incomingAttachment = $incoming->attachments()->firstOrFail();
    $outgoingAttachment = $outgoing->attachments()->firstOrFail();

    $this->get(route('documents.archive'))
        ->assertOk()
        ->assertDontSee($incoming->reference_number)
        ->assertDontSee($outgoing->reference_number);

    $this->put(route('documents.update', $incoming), [
        'reference_number' => $incoming->reference_number,
        'subject' => $incoming->subject,
        'document_date' => '2026-10-01',
        'recipient_department_id' => $incomingDepartmentId,
        'notes' => 'Incoming archive notes',
        'priority' => 'normal',
        'status' => 'closed',
    ])->assertRedirect(route('documents.show', $incoming));

    $this->put(route('documents.update', $outgoing), [
        'reference_number' => $outgoing->reference_number,
        'subject' => $outgoing->subject,
        'document_date' => '2026-10-03',
        'sending_department_id' => $outgoingDepartmentId,
        'notes' => 'Outgoing archive notes',
        'priority' => 'normal',
        'status' => 'closed',
    ])->assertRedirect(route('documents.show', $outgoing));

    $this->get(route('documents.archive'))
        ->assertOk()
        ->assertSee($incoming->reference_number)
        ->assertSee($outgoing->reference_number)
        ->assertSee('Incoming Office')
        ->assertSee('Outgoing Office');
    $this->assertDatabaseCount('documents', 2);
    $this->assertDatabaseCount('document_archives', 0);

    $this->get(route('documents.archive', ['direction' => 'incoming', 'date_from' => '2026-10-01', 'date_to' => '2026-10-01', 'department_id' => $incomingDepartmentId]))
        ->assertOk()
        ->assertSee($incoming->reference_number)
        ->assertDontSee($outgoing->reference_number);
    $this->get(route('documents.archive', ['reference_number' => 'OUT-2026-ARCHIVE-ATT', 'subject' => 'Outgoing document', 'status' => 'closed']))
        ->assertOk()
        ->assertSee($outgoing->reference_number)
        ->assertDontSee($incoming->reference_number);
    $this->get(route('documents.archive', ['status' => 'registered']))
        ->assertOk()
        ->assertDontSee($incoming->reference_number)
        ->assertDontSee($outgoing->reference_number);

    $this->get(route('documents.show', $incoming))
        ->assertOk()
        ->assertSee('Archived document')
        ->assertSee('Incoming Office')
        ->assertSee('Incoming archive notes')
        ->assertSee('archive-scan.jpg')
        ->assertSee(route('documents.edit', $incoming));
    $this->get(route('documents.attachments.open', [$incoming, $incomingAttachment]))->assertOk();
    $this->get(route('documents.attachments.download', [$outgoing, $outgoingAttachment]))->assertOk();
    $this->delete(route('documents.attachments.destroy', [$incoming, $incomingAttachment]))->assertForbidden();
    $this->assertDatabaseHas('document_attachments', ['id' => $incomingAttachment->id, 'document_id' => $incoming->id]);
    Storage::disk('local')->assertExists($incomingAttachment->storage_path);
    Storage::disk('local')->assertExists($outgoingAttachment->storage_path);

    $this->put(route('documents.update', $incoming), [
        'reference_number' => $incoming->reference_number,
        'subject' => $incoming->subject,
        'document_date' => '2026-10-01',
        'recipient_department_id' => $incomingDepartmentId,
        'notes' => 'Incoming archive notes',
        'priority' => 'normal',
        'status' => 'registered',
    ])->assertRedirect(route('documents.show', $incoming));

    $this->get(route('documents.archive'))
        ->assertOk()
        ->assertDontSee($incoming->reference_number)
        ->assertSee($outgoing->reference_number);
    $this->assertDatabaseCount('documents', 2);
    $this->assertDatabaseHas('document_attachments', ['id' => $incomingAttachment->id, 'document_id' => $incoming->id]);
});

it('deletes an attachment and its private file from an active document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Attachment Deletion Registry');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $this->actingAs($user)->post(route('documents.store', 'outgoing'), [
        'reference_number' => 'OUT-2026-DELETE-ATT',
        'subject' => 'Outgoing document with a removable attachment',
        'priority' => 'normal',
        'status' => 'registered',
        'attachments' => [UploadedFile::fake()->image('removable.png')],
    ])->assertRedirect();

    $document = Document::query()->where('reference_number', 'OUT-2026-DELETE-ATT')->firstOrFail();
    $attachment = $document->attachments()->firstOrFail();

    $this->delete(route('documents.attachments.destroy', [$document, $attachment]))
        ->assertRedirect(route('documents.show', $document));

    $this->assertDatabaseMissing('document_attachments', ['id' => $attachment->id]);
    Storage::disk('local')->assertMissing($attachment->storage_path);
});

it('filters organization documents and hides records belonging to another organization', function () {
    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Records Office A');
    $otherOrganizationId = createOrganizationForDocumentTests('Records Office B');
    assignDocumentTestUserToOrganization($user, $organizationId);

    $visibleDocumentId = DB::table('documents')->insertGetId([
        'organization_id' => $organizationId,
        'created_by' => $user->id,
        'reference_number' => 'IN-2026-0100',
        'direction' => 'incoming',
        'status' => 'registered',
        'priority' => 'normal',
        'subject' => 'Water services correspondence',
        'document_date' => '2026-10-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('documents')->insert([
        'organization_id' => $otherOrganizationId,
        'created_by' => $user->id,
        'reference_number' => 'IN-2026-0200',
        'direction' => 'incoming',
        'status' => 'registered',
        'priority' => 'normal',
        'subject' => 'Confidential other-office file',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('documents.incoming.index', ['reference_number' => '0100']))
        ->assertOk()
        ->assertSee('IN-2026-0100')
        ->assertDontSee('IN-2026-0200');

    $this->get(route('documents.show', $visibleDocumentId))->assertOk();
    $this->get(route('documents.show', 2))->assertNotFound();
});

it('rejects departments from a different organization', function () {
    $user = User::factory()->create();
    $organizationId = createOrganizationForDocumentTests('Records Office A');
    $otherOrganizationId = createOrganizationForDocumentTests('Records Office B');
    assignDocumentTestUserToOrganization($user, $organizationId);
    $otherDepartmentId = DB::table('departments')->insertGetId([
        'organization_id' => $otherOrganizationId,
        'name' => 'Other Office Department',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('documents.create', 'incoming'))
        ->post(route('documents.store', 'incoming'), [
            'reference_number' => 'IN-2026-0300',
            'subject' => 'Invalid department submission',
            'sending_department_id' => $otherDepartmentId,
            'priority' => 'normal',
            'status' => 'registered',
        ])
        ->assertRedirect(route('documents.create', 'incoming'))
        ->assertSessionHasErrors('sending_department_id');

    $this->assertDatabaseMissing('documents', ['reference_number' => 'IN-2026-0300']);
});

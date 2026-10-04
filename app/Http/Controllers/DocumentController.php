<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function dashboard(Request $request): View
    {
        $organization = $request->user()->currentOrganization();

        return view('documents.dashboard', [
            'organization' => $organization,
            'incomingCount' => $organization?->documents()->where('direction', 'incoming')->count() ?? 0,
            'outgoingCount' => $organization?->documents()->where('direction', 'outgoing')->count() ?? 0,
            'archiveCount' => $organization?->documents()->where('status', 'closed')->count() ?? 0,
        ]);
    }

    public function index(Request $request, string $direction): View
    {
        abort_unless(in_array($direction, ['incoming', 'outgoing'], true), 404);

        $organization = $request->user()->currentOrganization();
        $documents = $organization
            ? $this->documentQuery($organization, $direction, $request)->paginate(12)->withQueryString()
            : Document::whereRaw('1 = 0')->paginate(12);

        return view('documents.index', [
            'direction' => $direction,
            'documents' => $documents,
            'organization' => $organization,
            'departments' => $organization?->departments()->where('is_active', true)->orderBy('name')->get() ?? collect(),
        ]);
    }

    public function create(Request $request, string $direction): View
    {
        abort_unless(in_array($direction, ['incoming', 'outgoing'], true), 404);

        $organization = $request->user()->currentOrganization();

        return view('documents.form', [
            'direction' => $direction,
            'document' => new Document(['document_date' => today()]),
            'organization' => $organization,
            'departments' => $organization?->departments()->where('is_active', true)->orderBy('name')->get() ?? collect(),
            'documentTypes' => $organization?->documentTypes()->where('is_active', true)->orderBy('name')->get() ?? collect(),
        ]);
    }

    public function store(Request $request, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['incoming', 'outgoing'], true), 404);

        $organization = $request->user()->currentOrganization();
        abort_unless($organization, 403, __('documents.organization_required'));

        $data = $this->validatedDocument($request, $organization, $direction);

        $document = DB::transaction(function () use ($data, $direction, $organization, $request): Document {
            $partyName = $data['party_name'] ?? null;
            unset($data['party_name']);

            $document = $organization->documents()->create([
                ...$data,
                'direction' => $direction,
                'created_by' => $request->user()->id,
            ]);

            $this->saveParty($document, $direction, $partyName);

            return $document;
        });
        $this->storeAttachments($document, $request->file('attachments', []), $request->user()->id);

        return redirect()->route('documents.show', $document)->with('status', __('documents.created'));
    }

    public function show(Request $request, int $document): View
    {
        $record = $this->findDocument($request, $document);

        return view('documents.show', ['document' => $record]);
    }

    public function edit(Request $request, int $document): View
    {
        $record = $this->findDocument($request, $document);
        $organization = $request->user()->currentOrganization();

        return view('documents.form', [
            'direction' => $record->direction,
            'document' => $record,
            'organization' => $organization,
            'departments' => $organization?->departments()->where('is_active', true)->orderBy('name')->get() ?? collect(),
            'documentTypes' => $organization?->documentTypes()->where('is_active', true)->orderBy('name')->get() ?? collect(),
        ]);
    }

    public function update(Request $request, int $document): RedirectResponse
    {
        $record = $this->findDocument($request, $document);
        $organization = $request->user()->currentOrganization();
        abort_unless($organization, 403, __('documents.organization_required'));

        $data = $this->validatedDocument($request, $organization, $record->direction, $record);

        DB::transaction(function () use ($data, $record): void {
            $partyName = $data['party_name'] ?? null;
            unset($data['party_name']);
            $record->update($data);
            $this->saveParty($record, $record->direction, $partyName);
        });
        $this->storeAttachments($record, $request->file('attachments', []), $request->user()->id);

        return redirect()->route('documents.show', $record)->with('status', __('documents.updated'));
    }

    public function openAttachment(Request $request, int $document, int $attachment): BinaryFileResponse
    {
        $record = $this->findDocument($request, $document);
        $file = $this->findAttachment($record, $attachment);
        abort_unless(Storage::disk('local')->exists($file->storage_path), 404);

        return response()->file(Storage::disk('local')->path($file->storage_path), [
            'Content-Type' => $file->mime_type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadAttachment(Request $request, int $document, int $attachment): StreamedResponse
    {
        $record = $this->findDocument($request, $document);
        $file = $this->findAttachment($record, $attachment);
        abort_unless(Storage::disk('local')->exists($file->storage_path), 404);

        return Storage::disk('local')->download($file->storage_path, $file->original_file_name, [
            'Content-Type' => $file->mime_type ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteAttachment(Request $request, int $document, int $attachment): RedirectResponse
    {
        $record = $this->findDocument($request, $document);
        abort_if($record->status === 'closed' || $record->archive, 403, __('documents.archived_attachment_locked'));
        $file = $this->findAttachment($record, $attachment);

        Storage::disk('local')->delete($file->storage_path);
        $file->delete();

        return redirect()->route('documents.show', $record)->with('status', __('documents.attachment_deleted'));
    }

    public function archive(Request $request): View
    {
        $organization = $request->user()->currentOrganization();
        $departments = $organization?->departments()->where('is_active', true)->orderBy('name')->get() ?? collect();

        if ($organization) {
            $query = $organization->documents()
                ->where('status', 'closed')
                ->with(['sendingDepartment', 'recipientDepartment'])
                ->withCount('attachments');

            if ($request->filled('search')) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn (Builder $builder) => $builder
                    ->where('reference_number', 'like', $search)
                    ->orWhere('subject', 'like', $search));
            }

            foreach (['reference_number', 'subject'] as $field) {
                if ($request->filled($field)) {
                    $query->where($field, 'like', '%'.$request->string($field)->trim().'%');
                }
            }

            foreach (['direction', 'status'] as $field) {
                if ($request->filled($field)) {
                    $query->where($field, $request->string($field)->trim()->toString());
                }
            }

            if ($request->filled('department_id')) {
                $departmentId = $request->integer('department_id');
                $query->where(fn (Builder $builder) => $builder
                    ->where('sending_department_id', $departmentId)
                    ->orWhere('recipient_department_id', $departmentId));
            }

            if ($request->filled('date_from')) {
                $query->whereDate('document_date', '>=', $request->input('date_from'));
            }

            if ($request->filled('date_to')) {
                $query->whereDate('document_date', '<=', $request->input('date_to'));
            }

            $documents = $query->latest('document_date')->latest('id')->paginate(12)->withQueryString();
        } else {
            $documents = Document::whereRaw('1 = 0')->paginate(12);
        }

        return view('documents.archive', compact('documents', 'departments', 'organization'));
    }

    private function documentQuery(Organization $organization, string $direction, Request $request): HasMany
    {
        $query = $organization->documents()
            ->where('direction', $direction)
            ->with(['sendingDepartment', 'recipientDepartment', 'documentType', 'parties']);

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('parties', fn (Builder $partyQuery) => $partyQuery
                        ->where('organization_name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%"));
            });
        }

        foreach (['reference_number', 'subject'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, 'like', '%'.$request->string($field)->trim().'%');
            }
        }

        $partyType = $direction === 'incoming' ? 'sender' : 'recipient';
        $partyFilter = $direction === 'incoming' ? 'sender' : 'recipient';
        if ($request->filled($partyFilter)) {
            $party = '%'.$request->string($partyFilter)->trim().'%';
            $query->whereHas('parties', fn (Builder $partyQuery) => $partyQuery
                ->where('party_type', $partyType)
                ->where(fn (Builder $nameQuery) => $nameQuery
                    ->where('organization_name', 'like', $party)
                    ->orWhere('contact_name', 'like', $party)));
        }

        if ($request->filled('department_id')) {
            $departmentId = $request->integer('department_id');
            $query->where(fn (Builder $builder) => $builder
                ->where('sending_department_id', $departmentId)
                ->orWhere('recipient_department_id', $departmentId));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('document_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('document_date', '<=', $request->date('date_to'));
        }

        return $query->latest('document_date')->latest('id');
    }

    private function validatedDocument(Request $request, Organization $organization, string $direction, ?Document $document = null): array
    {
        return $request->validate([
            'reference_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('documents', 'reference_number')
                    ->where('organization_id', $organization->id)
                    ->where('direction', $direction)
                    ->ignore($document?->id),
            ],
            'document_date' => ['nullable', 'date'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'party_name' => ['nullable', 'string', 'max:255'],
            'sending_department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'recipient_department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('organization_id', $organization->id),
            ],
            'document_type_id' => [
                'nullable',
                Rule::exists('document_types', 'id')->where('organization_id', $organization->id),
            ],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'status' => ['required', Rule::in(['registered', 'under_review', 'assigned', 'responded', 'closed'])],
            'notes' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('10mb'), 'extensions:pdf,jpg,jpeg,png'],
        ]);

        unset($data['attachments']);

        return $data;
    }

    /** @param array<int, UploadedFile> $files */
    private function storeAttachments(Document $document, array $files, int $userId): void
    {
        foreach ($files as $file) {
            $path = $file->store('document-attachments/'.$document->organization_id.'/'.$document->id, 'local');
            abort_unless($path, 500, __('documents.attachment_store_failed'));

            $document->attachments()->create([
                'uploaded_by' => $userId,
                'original_file_name' => $file->getClientOriginalName(),
                'storage_disk' => 'local',
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
        }
    }

    private function findAttachment(Document $document, int $attachment): DocumentAttachment
    {
        return $document->attachments()
            ->whereNotNull('storage_path')
            ->findOrFail($attachment);
    }

    private function saveParty(Document $document, string $direction, ?string $name): void
    {
        $partyType = $direction === 'incoming' ? 'sender' : 'recipient';
        $party = $document->parties()->where('party_type', $partyType)->first();

        if (blank($name)) {
            $party?->delete();

            return;
        }

        if ($party) {
            $party->update(['organization_name' => $name]);
        } else {
            $document->parties()->create([
                'party_type' => $partyType,
                'organization_name' => $name,
            ]);
        }
    }

    private function findDocument(Request $request, int $document): Document
    {
        $organization = $request->user()->currentOrganization();
        abort_unless($organization, 404);

        return $organization->documents()
            ->with(['sendingDepartment', 'recipientDepartment', 'documentType', 'parties', 'archive', 'attachments'])
            ->findOrFail($document);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'organization_id',
    'recipient_department_id',
    'sending_department_id',
    'document_type_id',
    'created_by',
    'assigned_to',
    'reference_number',
    'direction',
    'status',
    'priority',
    'confidentiality',
    'subject',
    'summary',
    'notes',
    'document_date',
    'received_at',
    'sent_at',
    'external_reference',
    'response_due_at',
])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
            'response_due_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sendingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sending_department_id');
    }

    public function recipientDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'recipient_department_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(DocumentParty::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DocumentAttachment::class);
    }

    public function archive(): HasOne
    {
        return $this->hasOne(DocumentArchive::class);
    }
}

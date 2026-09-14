<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KYC extends Model
{
    use HasFactory;

    protected $table = 'kycs';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'nationality',
        'document_type',
        'document_number',
        'document_expiry_date',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'phone_number',
        'document_front_path',
        'document_back_path',
        'selfie_path',
        'status',
        'rejection_reason',
        'submitted_at',
        'verified_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'document_expiry_date' => 'date',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getDocumentTypeLabelAttribute()
    {
        $labels = [
            'passport' => 'Passport',
            'national_id' => 'National ID',
            'drivers_license' => 'Driver\'s License',
        ];

        return $labels[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isExpired()
    {
        return $this->document_expiry_date && $this->document_expiry_date->isPast();
    }

    public function getFormattedDateOfBirthAttribute()
    {
        return $this->date_of_birth?->format('M j, Y');
    }

    public function getFormattedDocumentExpiryAttribute()
    {
        return $this->document_expiry_date?->format('M j, Y');
    }

    public function getFormattedSubmittedAtAttribute()
    {
        return $this->submitted_at?->format('M j, Y g:i A');
    }

    public function getFormattedVerifiedAtAttribute()
    {
        return $this->verified_at?->format('M j, Y g:i A');
    }
}

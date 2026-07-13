<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    // Jabatan (structural identity, PRD v2 §2.1). Drives the approval flow.
    public const JABATAN_STAFF = 'staff';
    public const JABATAN_GROUP_LEADER = 'group_leader';
    public const JABATAN_SECTION_HEAD = 'section_head';
    public const JABATAN_PIMPINAN = 'pimpinan';

    public const JABATAN_LABELS = [
        self::JABATAN_STAFF => 'Staff',
        self::JABATAN_GROUP_LEADER => 'Group Leader',
        self::JABATAN_SECTION_HEAD => 'Section Head',
        self::JABATAN_PIMPINAN => 'Pimpinan',
    ];

    protected $fillable = [
        'name',
        'username',
        'nrp',
        'jabatan',
        'nomor_hp',
        'email',
        'password',
        'department_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\LogsModelActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuids, LogsModelActivity, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if (!$user->role) {
                return;
            }

            if (!Schema::hasTable(config('permission.table_names.roles'))) {
                return;
            }

            $roleName = $user->role;

            if (!Role::query()->where('name', $roleName)->exists()) {
                return;
            }

            if (!$user->roles->contains('name', $roleName)) {
                $user->syncRoles([$roleName]);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'national_id',
        'passport_number',
        'password_hash',
        'role',
        'branch_id',
        'status',
        'hire_date',
        'termination_date',
        'employment_type',
        'commission_rate',
        'commission_type',
        'base_salary',
        'last_login_at',
        'last_login_ip',
        'failed_login_count',
        'locked_until',
        'avatar_url',
        'bio',
        'skills',
        'settings',
        'preferences',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'settings' => 'array',
            'preferences' => 'array',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'hire_date' => 'date',
            'termination_date' => 'date',
            'commission_rate' => 'decimal:2',
            'base_salary' => 'decimal:2',
            'skills' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return $this->baseActivitylogOptions()
            ->logExcept([
                'password_hash',
                'last_login_at',
                'last_login_ip',
                'failed_login_count',
                'locked_until',
            ]);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function managedBranches(): HasMany
    {
        return $this->hasMany(Branch::class, 'manager_id');
    }

    /**
     * @return array<int, string>
     */
    public static function employeeRoles(): array
    {
        return ['barber', 'manager', 'receptionist', 'other'];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function dailyEntries(): HasMany
    {
        return $this->hasMany(DailyEntry::class, 'employee_id');
    }

    public function advanceRequests(): HasMany
    {
        return $this->hasMany(AdvanceRequest::class, 'employee_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id')->where('owner_type', 'employee'); // Keeping 'employee' for now as per migration
    }
}

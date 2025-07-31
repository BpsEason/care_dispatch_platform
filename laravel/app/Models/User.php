<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'supervisor_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // 新增角色判斷方法
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isCaregiver(): bool
    {
        return in_array($this->role, ['caregiver', 'nutritionist', 'physiotherapist']);
    }

    // 與其下屬居服員的關係 (如果使用者是督導)
    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    // 與其督導的關係 (如果使用者是居服員)
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // 與名下個案的關係 (如果使用者是督導)
    public function patients()
    {
        return $this->hasMany(Patient::class, 'supervisor_id');
    }

    // 與其負責的派工的關係 (如果使用者是居服員)
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assigned_to_user_id');
    }

    // 與其打卡記錄的關係
    public function clockRecords()
    {
        return $this->hasMany(ClockRecord::class, 'user_id');
    }

    // 與其請假申請的關係
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    // 與其薪資單的關係
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'user_id');
    }
}

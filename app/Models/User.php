<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'role',
        'phone',
        'email_verified_at',
        'nafath_id',
        'is_active',
        'remember_token',
        'mosque_admin_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the mosques where this user is the admin.
     */
    public function mosques()
    {
        return $this->hasMany(Mosque::class, 'mosque_admin_id');
    }

    /**
     * Get the donations made by this user.
     */
    public function donations()
    {
        return $this->hasMany(Donation::class, 'donor_id');
    }

    /**
     * Get the donations verified by this user.
     */
    public function verifiedDonations()
    {
        return $this->hasMany(Donation::class, 'verified_by');
    }

    /**
     * Get the need requests created by this user.
     */
    public function needRequests()
    {
        return $this->hasMany(NeedRequest::class, 'requested_by');
    }

    /**
     * Get the need requests approved by this user.
     */
    public function approvedNeedRequests()
    {
        return $this->hasMany(NeedRequest::class, 'approved_by');
    }

    /**
     * Get the tank images uploaded by this user.
     */
    public function tankImages()
    {
        return $this->hasMany(TankImage::class, 'uploaded_by');
    }

    /**
     * Get the trucks assigned to this user as driver.
     */
    public function trucks()
    {
        return $this->hasMany(Truck::class, 'assigned_driver_id');
    }

    /**
     * Get the deliveries made by this user.
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'delivered_by');
    }

    /**
     * Get the campaigns created by this user.
     */
    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    /**
     * Get the ads created by this user.
     */
    public function ads()
    {
        return $this->hasMany(Ad::class, 'created_by');
    }

    /**
     * Get the activity logs for this user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * According to specification: Token includes: user_id, role, email
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'role' => $this->role,
            'email' => $this->email,
        ];
    }
}

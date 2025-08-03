<?php
// app/Models/User.php

namespace App\Models;

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
        'profile_image',
        'phone',
        'date_of_birth',
        'is_seller',
        'is_verified',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_seller' => 'boolean',
        'is_verified' => 'boolean',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function vendorProfile()
    {
        return $this->hasOne(VendorProfile::class);
    }

    // Role helper methods
    public function isAdmin()
    {
        return $this->role && $this->role->name === Role::ADMIN;
    }

    public function isVendor()
    {
        return $this->role && $this->role->name === Role::VENDOR;
    }

    public function isCustomer()
    {
        return $this->role && $this->role->name === Role::CUSTOMER;
    }

    public function hasRole($role)
    {
        return $this->role && $this->role->name === $role;
    }

    // Scopes
    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('role', function($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    public function scopeVendors($query)
    {
        return $query->byRole(Role::VENDOR);
    }

    public function scopeCustomers($query)
    {
        return $query->byRole(Role::CUSTOMER);
    }
}
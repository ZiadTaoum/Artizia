<?php
// app/Models/Role.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    // Role constants
    const ADMIN = 'admin';
    const VENDOR = 'vendor';
    const CUSTOMER = 'customer';

    // Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->name === self::ADMIN;
    }

    public function isVendor()
    {
        return $this->name === self::VENDOR;
    }

    public function isCustomer()
    {
        return $this->name === self::CUSTOMER;
    }
}
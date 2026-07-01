<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    // [UPDATE]: Secara eksplisit memberitahu Spatie Permission agar selalu mengecek 
    // database role berdasarkan guard 'web', meskipun kita sedang login via guard 'admin'
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_active',
        'notification_email',
        'email_verified_at',
        'google_id',
        'github_id',
        'discord_id',
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
            'is_active' => 'boolean',
            'notification_email' => 'boolean',
        ];
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }

}

<?php

namespace Workbench\App\Models;

use AlturaCode\Billing\Laravel\Billable;
use AlturaCode\Billing\Laravel\HasBilling;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements Billable
{
    /** @use HasFactory<\Workbench\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasBilling;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function newFactory(): \Workbench\Database\Factories\UserFactory
    {
        return \Workbench\Database\Factories\UserFactory::new();
    }
}

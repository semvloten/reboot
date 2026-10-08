<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Onderdeel: Gebruikersopslag met verborgen wachtwoord en hashed cast; rollen zijn niet vrij massaal toewijsbaar.
 * Eisen: FE-01, FE-13, RV-02, RV-03, TE-02, TE-03, TE-06.
 * Bouw: T-04 (accountaanmaak), T-25 (roltoegang), T-27 (wachtwoordopslag), T-31 (structuur).
 * Geplande controle: T-05, T-26, T-28, T-32.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
}

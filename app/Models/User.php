<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'wikidata_id',
        'wikidata_username',
        'wikidata_token',
        'wikidata_token_secret',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'wikidata_token',
        'wikidata_token_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    /**
     * Pru00fcft, ob der Benutzer ein Administrator ist.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->is_admin === true;
    }

    /**
     * Pru00fcft, ob der Benutzer mit Wikidata verbunden ist.
     *
     * @return bool
     */
    public function hasWikidataAccount()
    {
        return !empty($this->wikidata_id) && !empty($this->wikidata_username);
    }
}

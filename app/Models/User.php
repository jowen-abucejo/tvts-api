<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ["name", "username", "password", "user_type"];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];

    /**
     * Find the user instance for the given username.
     *
     * @param  string  $username
     * @return \App\Models\User
     */
    public function findForPassport($username)
    {
        return $this->where("username", $username)->first();
    }

    public function isAdmin()
    {
        if (
            $this->user_type == "admin" ||
            $this->user_type == "deputy officer"
        ) {
            return true;
        }
        return false;
    }

    public function isTreasury()
    {
        if ($this->user_type == "treasury" || $this->user_type == "admin") {
            return true;
        }
        return false;
    }

    public function isEnforcer()
    {
        if ($this->user_type == "officer") {
            return true;
        }
        return false;
    }

    /**
     * Get all of the ticket issued by the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketIssued()
    {
        return $this->hasMany(Ticket::class, "issued_by", "id")->withTrashed();
    }

    /**
     * Get all of the ticket issued by the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, "user_id", "id");
    }
}

<?php

namespace App\Models;

use App\Casts\CostaRicaDatetime;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

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
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => CostaRicaDatetime::class,
            'password' => 'hashed',
        ];
    }

    /**
     * Scope a query to only include users with the admin role.
     *
     * @param  Builder  $query  The query builder instance.
     */
    public function scopeAdmins(Builder $query): void
    {
        $query->whereHas('roles', function (Builder $query) {
            $query->where('name', UserRole::ADMIN->value);
        });
    }

    /**
     * Mutator to interact with the user's password.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => empty($value) ? $this->password : bcrypt($value),
        );
    }

    /**
     * Get the employee record associated with the user.
     *
     * @return HasOne<Employee>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'id', 'id');
    }
}

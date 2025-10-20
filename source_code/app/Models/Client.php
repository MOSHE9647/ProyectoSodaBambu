<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';
    protected $primaryKey = 'id';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'registration_date',
    ];

    protected $casts = [
        'registration_date' => 'date',
    ];
}


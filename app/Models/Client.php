<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name', 'company', 'address', 'email', 'phone', 'sender_id',
    ];

    public function user()
    {
        return $this->hasOne(\App\Models\User::class);
    }
}

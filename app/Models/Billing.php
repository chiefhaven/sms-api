<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    /** @use HasFactory<\Database\Factories\BillingFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'bill_number',
        'client_id',
        'type',
        'date',
        'due_date',
        'items',
        'notes',
        'status',
        'email',
        'phone',
        'total_amount',
        'completion_notes',
    ];


    protected $casts = [
        'items' => 'array',
        'date' => 'date',
        'due_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

}

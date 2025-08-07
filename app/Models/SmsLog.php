<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    /** @use HasFactory<\Database\Factories\SmsLogFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'client_id',
        'message_id',
        'recipient',
        'message',
        'message_parts',
        'cost',
        'new_balance',
        'status',
        'status_code',
        'description',
        'mnc',
        'mcc',
        'gateway_response',
        'sender_id',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'cost' => 'decimal:2',
        'new_balance' => 'decimal:2',
    ];
}

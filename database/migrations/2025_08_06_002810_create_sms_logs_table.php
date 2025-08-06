<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('client_id')->constrained();

            // Backbone API specific fields
            $table->uuid('message_id')->nullable()->index();
            $table->string('recipient', 20);
            $table->text('message');
            $table->unsignedSmallInteger('message_parts');
            $table->decimal('cost', 10, 2);
            $table->decimal('new_balance', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->string('status_code', 10);
            $table->string('description');

            // Backbone metadata
            $table->string('mnc', 10)->nullable();
            $table->string('mcc', 10)->nullable();

            $table->json('gateway_response');
            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index('recipient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};

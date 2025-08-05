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
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();

            $table->string('to')->comment('Recipient phone number');
            $table->string('from')->comment('Sender phone number');
            $table->text('message')->comment('SMS content');

            $table->enum('status', ['pending', 'sent', 'failed'])
                  ->default('pending')
                  ->comment('Status of the SMS');

            $table->string('provider')->nullable()->comment('SMS service provider used for sending');
            $table->string('provider_id')->nullable()->comment('ID returned by the SMS service provider');

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('to');
            $table->index('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};

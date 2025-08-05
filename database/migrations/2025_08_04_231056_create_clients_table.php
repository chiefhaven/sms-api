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
        Schema::create('clients', function (Blueprint $table) {
                $table->uuid('id')->primary()->comment('Unique identifier for the client');

                $table->string('name')->comment('Client name');
                $table->string('company')->nullable()->comment('Client company name');
                $table->string('address')->nullable()->comment('Client address');
                $table->string('email')->unique()->comment('Client email address');
                $table->string('phone')->nullable()->comment('Client phone number');
                $table->string('sender_id')->unique()->comment('Unique sender ID for SMS');

                $table->timestamps();

                // Indexes
                $table->index('sender_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

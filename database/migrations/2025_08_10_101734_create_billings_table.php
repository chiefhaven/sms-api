<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();

            $table->string('bill_number')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            $table->enum('type', ['Invoice', 'Quotation']);
            $table->date('date');
            $table->date('due_date')->nullable();

            // Store items as JSON
            $table->json('items');

            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->text('completion_notes')->nullable();

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};

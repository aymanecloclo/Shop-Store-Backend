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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->dateTime('payment_date');
        $table->decimal('amount', 10, 2);
        $table->enum('payment_method', ['Card', 'PayPal', 'Cash']);
        $table->enum('status', ['successful', 'pending', 'failed']);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

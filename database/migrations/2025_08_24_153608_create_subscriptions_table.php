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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained();
            $table->timestamp('start_at');
            $table->timestamp('end_at')->nullable();
            $table->enum('status', ['active', 'expired', 'pending', 'cancelled'])->default('pending');
            $table->boolean('is_trial')->default(false);
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_end_at')->nullable();
            $table->foreignId('current_invoice_id')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['status', 'end_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

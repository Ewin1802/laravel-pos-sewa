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
        Schema::table('license_tokens', function (Blueprint $table) {
            $table->text('plain_token')->nullable()->after('token');
            $table->foreignId('subscription_id')->nullable()->constrained()->onDelete('cascade')->after('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_tokens', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropColumn(['plain_token', 'subscription_id']);
        });
    }
};

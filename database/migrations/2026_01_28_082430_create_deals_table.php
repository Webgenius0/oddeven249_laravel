<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('advertiser_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->string('campaign_name');
            $table->double('amount', 15, 2);
            $table->text('description')->nullable();
            $table->datetime('valid_until');
            $table->string('duration');
            $table->enum('status', [
               'pending', 'active', 'delivered',
               'completed', 'rejected', 'expired',
               'disputed', 'refunded'
            ])->default('pending')
            ->comment('Current state of the deal');

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};

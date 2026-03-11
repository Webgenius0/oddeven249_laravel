<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('event_ticket_id')->constrained('event_tickets')->onDelete('cascade');
            $table->string('ticket_code', 10)->unique()->nullable()->index();
            $table->enum('payment_status', ['pending', 'paid', 'free', 'rejected'])->default('pending');
            $table->integer('quantity')->default(1);
            $table->integer('used_quantity')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};

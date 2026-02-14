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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade')->comment('The user who created the event');
            $table->string('title')->comment('Title of the event');
            $table->enum('type', ['online', 'offline'])->comment('Type of event (e.g. Online, Offline)');
            $table->decimal('entry_fee', 8, 2)->default(0);
            $table->string('location')->nullable()->comment('City or General Area');
            $table->text('full_location')->nullable()->comment('Detailed address');
            $table->dateTime('date')->nullable()->comment('Date and time of the event');
            $table->text('description')->nullable();
            $table->string('photo')->nullable()->comment('Event cover or thumbnail URL');
            $table->enum('event_restriction', ['public', 'only_invited'])->default('public')->comment('public/only_invited');
            $table->boolean('is_published')->default(false);
            $table->text('message')->nullable()->comment('Message for participants or invitations');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active')->comment('active/completed/cancelled');
            $table->timestamps(); // created_at + updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

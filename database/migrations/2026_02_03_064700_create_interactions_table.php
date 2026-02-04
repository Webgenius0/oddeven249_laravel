<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->morphs('target');
            // Interaction type (like, view, click, etc.)
            $table->string('interaction_type');

            // Analytics data
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            //index for performance(e.g., query by interaction type)
            $table->index(['target_id', 'target_type', 'interaction_type']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};

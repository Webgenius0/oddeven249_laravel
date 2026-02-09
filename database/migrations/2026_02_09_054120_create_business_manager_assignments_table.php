<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('business_manager_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
            $table->json('permissions')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_manager_assignments');
    }
};

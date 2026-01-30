<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    public function up(): void
    {
        Schema::create('deal_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('rated_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('rated_to')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating');
            $table->text('message')->nullable();
            $table->timestamps();

            
            $table->unique(['deal_id', 'rated_by']);
        });
    }
  
    public function down(): void
    {
        Schema::dropIfExists('deal_ratings');
    }
};

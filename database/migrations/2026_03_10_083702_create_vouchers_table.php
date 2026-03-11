<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('promo_code')->unique();
            $table->text('description')->nullable();
            $table->decimal('discount', 10, 2);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('fixed');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};

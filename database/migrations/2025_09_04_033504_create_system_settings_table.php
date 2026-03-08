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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('system_name')->nullable();
            $table->string('copyright_text')->nullable();
            $table->decimal('platform_commission', 8, 2)->default(0.00)->comment('Percentage of commission');
            $table->decimal('tax_rate', 8, 2)->default(0.00)->comment('Percentage of tax');
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

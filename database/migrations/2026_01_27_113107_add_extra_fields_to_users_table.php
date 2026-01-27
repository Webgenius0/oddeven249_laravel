<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('website_link')->nullable()->after('email'); 
            $table->foreignId('category_id')->nullable()->after('website_link')
                  ->constrained('categories') 
                  ->onDelete('set null'); 
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['website_link', 'category_id']);
        });
    }
};

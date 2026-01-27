<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('website_link')->nullable()->after('role');
            $table->unsignedBigInteger('category_id')->nullable()->after('website_link');
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropColumn(['website_link', 'category_id']);
        });
    }
};

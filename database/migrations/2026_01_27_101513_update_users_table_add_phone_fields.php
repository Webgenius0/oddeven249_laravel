<?php

use Illuminate\Database\Schema\Blueprint; // Ei import ta thaka jaruri
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    public function up(): void
    {
        // Table $table er jaygay Blueprint $table hobe
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->string('phone_code', 10)->nullable()->after('email');
            $table->string('phone', 20)->nullable()->after('phone_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_code', 'phone']);
            $table->string('code')->nullable();
        });
    }
};

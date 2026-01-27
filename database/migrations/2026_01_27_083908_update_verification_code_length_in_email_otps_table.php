<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('verification_code', 100)->change();
        });
    }

    public function down()
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('verification_code', 10)->change();
        });
    }
};

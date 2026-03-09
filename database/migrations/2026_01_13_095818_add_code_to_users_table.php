<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code')->nullable()->after('email');
            $table->string('document')->nullable();
            $table->string('website_link')->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->after('website_link');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_connect_id')->nullable();
            $table->boolean('stripe_connect_enabled')->default(false);

        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};

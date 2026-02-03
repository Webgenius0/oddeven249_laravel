<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->renameColumn('advertiser_id', 'buyer_id');
            $table->renameColumn('influencer_id', 'seller_id');
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->renameColumn('buyer_id', 'advertiser_id');
            $table->renameColumn('seller_id', 'influencer_id');
        });
    }
};

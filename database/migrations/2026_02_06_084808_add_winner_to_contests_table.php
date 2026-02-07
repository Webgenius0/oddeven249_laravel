<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->string('status')->default('active');
        });
    }
    public function down(): void
    {
        Schema::table('contests', function (Blueprint $table) {
            //
        });
    }
};

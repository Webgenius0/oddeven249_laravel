<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_suspended')->default(false)->after('email_verified_at');
            $table->string('suspension_reason')->nullable()->after('is_suspended');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_at');
            $table->timestamp('last_login_at')->nullable()->after('suspended_by');
            $table->boolean('two_fa_enabled')->default(false)->after('last_login_at');
            $table->string('two_fa_secret')->nullable()->after('two_fa_enabled');
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_suspended',
                'suspension_reason',
                'suspended_at',
                'suspended_by',
                'last_login_at',
                'two_fa_enabled',
                'two_fa_secret',
            ]);
        });
    }
};

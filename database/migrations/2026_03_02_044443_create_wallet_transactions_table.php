<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Transaction type
            $table->enum('type', [
                'deposit',
                'withdrawal',
                'hold',
                'release',
                'commission_deduction',
                'deal_payment',
                'contest_prize',
                'refund',
                'tax_deduction',            // ✅ যোগ করো
                'withdrawal_reversal',      // ✅ যোগ করো
                'event_ticket_payment',     // ✅ যোগ করো
            ]);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            $table->string('source_type')->nullable(); // deal, contest, manual, withdrawal
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('completed');
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

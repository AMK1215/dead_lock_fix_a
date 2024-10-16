<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_archives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('payable_type', 191);
            $table->unsignedBigInteger('payable_id');
            $table->unsignedBigInteger('wallet_id');
            $table->enum('type', ['deposit', 'withdraw'])->index();
            $table->decimal('amount', 64, 2)->default(0);
            $table->boolean('confirmed')->default(true);
            $table->longText('meta')->nullable();
            $table->char('uuid', 36)->unique();
            $table->timestamps();  // includes created_at and updated_at
            $table->string('event_id', 191)->nullable();
            $table->string('seamless_transaction_id', 191)->nullable();
            $table->string('name', 100)->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->boolean('is_report_generated')->default(0);
            $table->unsignedBigInteger('wager_id')->nullable();
            $table->text('note')->nullable();

            // Indexes
            $table->index(['payable_type', 'payable_id'], 'payable_type_payable_id_ind');
            $table->index(['payable_type', 'payable_id', 'type'], 'payable_type_ind');
            $table->index(['payable_type', 'payable_id', 'confirmed'], 'payable_confirmed_ind');
            $table->index(['payable_type', 'payable_id', 'type', 'confirmed'], 'payable_type_confirmed_ind');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_archives');
    }
};

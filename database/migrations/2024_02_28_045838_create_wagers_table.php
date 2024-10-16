<?php

use App\Enums\WagerStatus;
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
        Schema::create('wagers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seamless_wager_id')->unique();  // Unique index already applied
            $table->unsignedBigInteger('user_id')->nullable()->index();  // Index on user_id for performance
            $table->string('status')->default(WagerStatus::Ongoing->value)->index();  // Index on status if frequently queried
            $table->timestamps();
            // $table->id();
            // $table->unsignedBigInteger('seamless_wager_id')->unique();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wagers');
    }
};

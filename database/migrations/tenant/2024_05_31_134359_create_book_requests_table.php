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
        Schema::create('book_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('book_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('borrower_id')->index();
            $table->string('start_date');
            $table->string('end_date');
            $table->string('returned')->default('0');
            $table->string('status')->nullable();
            $table->tinyText('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_requests');
    }
};

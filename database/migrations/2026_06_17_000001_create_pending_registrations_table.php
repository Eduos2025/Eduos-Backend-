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
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('plan_id');
            $table->string('billing_interval');
            $table->string('subdomain');
            $table->string('school_name');
            $table->string('school_email');
            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_password');
            $table->decimal('amount', 10, 2);
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};

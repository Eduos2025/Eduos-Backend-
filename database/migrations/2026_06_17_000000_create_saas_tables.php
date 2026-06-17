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
        // 1. Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('yearly_price', 10, 2);
            $table->integer('trial_days')->default(14);
            $table->integer('max_students')->default(100);
            $table->integer('max_staff')->default(20);
            $table->integer('max_branches')->default(1);
            $table->boolean('active')->default(true);
            $table->json('features')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Add columns to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('plan_id')->nullable()->after('id');
            $table->string('subscription_status', 50)->default('trialing')->after('plan_id');
            $table->timestamp('expires_at')->nullable()->after('subscription_status');

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
        });

        // 3. Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id');
            $table->string('status', 50)->default('trialing');
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });

        // 4. Invoices Table
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('status', 50)->default('unpaid'); // unpaid, paid, void
            $table->timestamp('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
        });

        // 5. Subscription Payments Table
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('gateway', 50); // paystack, flutterwave
            $table->string('reference')->unique();
            $table->string('status', 50)->default('pending'); // pending, successful, failed
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });

        // 6. Coupons Table
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('discount_type', 50); // percent, fixed
            $table->decimal('value', 10, 2);
            $table->integer('max_uses')->default(1);
            $table->integer('used_count')->default(0);
            $table->json('plan_restrictions')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 7. Subscription Logs Table
        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('action');
            $table->text('description');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_logs');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'subscription_status', 'expires_at']);
        });

        Schema::dropIfExists('plans');
    }
};

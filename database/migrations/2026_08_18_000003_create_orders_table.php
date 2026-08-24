<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();

            $table->enum('status', [
                'pending',
                'created',
                'in_delivery',
                'delivered',
                'cancelled',
            ])->default('pending')->index();

            $table->enum('payment_method', ['cash_on_delivery', 'online'])->nullable();
            $table->enum('payment_status', ['pending', 'link_sent', 'paid', 'failed', 'cancelled'])
                ->default('pending')->index();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('location_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['delivery_id', 'status']);
            $table->index(['status', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

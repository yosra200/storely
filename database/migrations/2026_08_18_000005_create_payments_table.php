<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('payment_url')->nullable();
            $table->enum('status', ['pending', 'link_sent', 'paid', 'failed', 'cancelled'])
                ->default('pending')->index();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('EGP');
            $table->json('provider_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

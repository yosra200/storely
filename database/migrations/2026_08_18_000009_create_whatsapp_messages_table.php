<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['outbound', 'inbound']);
            $table->enum('message_type', [
                'address_request',
                'payment_request',
                'reminder',
                'confirmation',
                'payment_confirmation',
                'customer_reply',
                'other',
            ])->default('other');
            $table->string('recipient_phone')->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('template_name')->nullable();
            $table->text('body')->nullable();
            $table->enum('status', ['queued', 'sent', 'delivered', 'read', 'failed'])
                ->default('queued')->index();
            $table->json('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};

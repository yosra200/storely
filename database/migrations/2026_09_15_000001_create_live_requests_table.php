<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('live_id')->unique();

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'accepted', 'rejected'])
                ->default('pending')
                ->index();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_requests');
    }
};

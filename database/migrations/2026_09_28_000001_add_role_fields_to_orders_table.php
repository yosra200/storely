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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'sales_id')) {
                $table->foreignId('sales_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('sales_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'packing_id')) {
                $table->foreignId('packing_id')->nullable()->after('supervisor_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'packing_id')) {
                $table->dropConstrainedForeignId('packing_id');
            }

            if (Schema::hasColumn('orders', 'supervisor_id')) {
                $table->dropConstrainedForeignId('supervisor_id');
            }

            if (Schema::hasColumn('orders', 'sales_id')) {
                $table->dropConstrainedForeignId('sales_id');
            }
        });
    }
};

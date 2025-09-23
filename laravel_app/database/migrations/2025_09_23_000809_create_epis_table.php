<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('epis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_category_id')->constrained('epi_categories')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('label');
            $table->string('size')->nullable();
            $table->string('brand')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->date('purchase_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['expiration_date','is_active']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('epis');
    }
};

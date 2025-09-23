<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->enum('type', ['in','out','adjustment']);
            $table->integer('quantity');
            $table->string('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();
            $table->index(['epi_id','type','performed_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('inventory_movements');
    }
};

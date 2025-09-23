<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('epi_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->date('inspected_at');
            $table->string('inspected_by')->nullable();
            $table->enum('status', ['ok','repair','replace','discard'])->default('ok');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->index(['epi_id','inspected_at','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('epi_inspections');
    }
};

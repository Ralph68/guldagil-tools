<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('epi_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('epi_id')->constrained('epis')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('assigned_at');
            $table->date('returned_at')->nullable();
            $table->string('condition_on_issue')->nullable();
            $table->string('condition_on_return')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'epi_id', 'assigned_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('epi_assignments');
    }
};

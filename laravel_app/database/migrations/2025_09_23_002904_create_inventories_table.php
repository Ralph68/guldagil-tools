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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('epi_id');          // lien vers le matériel EPI
            $table->string('type');                        // type d’opération (entrée, sortie, etc.)
            $table->integer('quantity');                   // quantité ajoutée/sortie
            $table->string('reason')->nullable();          // raison de l’opération
            $table->unsignedBigInteger('performed_by');    // employé qui a fait l’opération
            $table->timestamp('performed_at')->nullable(); // date/heure
            $table->timestamps();

            // Clés étrangères
            $table->foreign('epi_id')
                  ->references('id')->on('epis')
                  ->onDelete('cascade');

            $table->foreign('performed_by')
                  ->references('id')->on('employees')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};

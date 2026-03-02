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
        Schema::create('organizers', function (Blueprint $table) {
            $table->id();

            // Nome do organizador (empresa ou pessoa)
            $table->string('name');

            // Documento (CPF ou CNPJ)
            $table->string('document')->unique();

            // Contato principal
            $table->string('email')->unique();
            $table->string('phone');

            // Endereço (para nota fiscal)
            $table->string('address'); // Logradouro + número
            $table->string('address_complement')->nullable();
            $table->string('neighborhood'); // Bairro
            $table->string('city');
            $table->string('state', 2); // UF
            $table->string('zip_code', 10); // CEP

            // Status do organizador na plataforma
            $table->string('status')->default('active');
            // active | suspended | blocked

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizers');
    }
};

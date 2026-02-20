<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Distância da categoria (em km) - ex: 5, 10, 21, 42
            $table->decimal('distance', 8, 2)->after('name');
            
            // Descrição opcional da categoria
            $table->text('description')->nullable()->after('distance');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['distance', 'description']);
        });
    }
};

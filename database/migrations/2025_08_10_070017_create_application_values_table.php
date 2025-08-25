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
        Schema::create('application_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->enum('criteria_type', ['criteria', 'subcriteria', 'subsubcriteria']);
            $table->unsignedBigInteger('criteria_id');
            $table->string('value'); // Nilai yang diinput siswa (contoh: 'petani', 'kurang_500k', dll)
            $table->decimal('score', 12, 10)->default(0); // Skor yang dihitung dari nilai tersebut
            $table->timestamps();

            // Index untuk optimasi query
            $table->index(['application_id', 'criteria_type']);
            $table->index(['criteria_type', 'criteria_id']);
            $table->unique(['application_id', 'criteria_type', 'criteria_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_values');
    }
};
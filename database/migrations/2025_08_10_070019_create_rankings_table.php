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
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->decimal('total_score', 12, 10)->default(0);
            $table->json('criteria_scores')->nullable(); // Menyimpan breakdown skor per kriteria
            $table->integer('rank')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            // Index untuk optimasi query
            $table->index(['period_id', 'rank']);
            $table->index(['period_id', 'total_score']);
            $table->unique(['period_id', 'application_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
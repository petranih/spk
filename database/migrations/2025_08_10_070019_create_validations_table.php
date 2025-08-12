<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->decimal('total_score', 10, 8);
            $table->integer('rank');
            $table->json('criteria_scores')->nullable();
            $table->timestamps();
            
            $table->unique(['period_id', 'application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rankings');
    }
};
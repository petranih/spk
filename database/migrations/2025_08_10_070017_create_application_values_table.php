<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('criteria_type'); // criteria, subcriteria, subsubcriteria
            $table->unsignedBigInteger('criteria_id');
            $table->text('value'); // JSON or string value
            $table->decimal('score', 10, 8)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_values');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('period_id')->constrained()->onDelete('cascade');
            $table->string('application_number')->unique();
            
            // Personal Data
            $table->string('full_name');
            $table->string('nisn');
            $table->string('school');
            $table->string('class');
            $table->date('birth_date');
            $table->string('birth_place');
            $table->enum('gender', ['L', 'P']);
            $table->text('address');
            $table->string('phone');
            
            // Status
            $table->enum('status', ['draft', 'submitted', 'validated', 'rejected', 'approved'])->default('draft');
            $table->text('notes')->nullable();
            $table->decimal('final_score', 10, 8)->nullable();
            $table->integer('rank')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
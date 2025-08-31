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
        Schema::create('validations', function (Blueprint $table) {
            $table->id();

            // Foreign key ke applications
            $table->foreignId('application_id')
                ->constrained('applications')
                ->onDelete('cascade');

            // Foreign key ke users (sebagai validator)
            $table->foreignId('validator_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Status validasi (pending, approved, rejected)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Catatan opsional
            $table->text('notes')->nullable();

            // Waktu validasi
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validations');
    }
};

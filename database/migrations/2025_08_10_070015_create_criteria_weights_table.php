<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria_weights', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // criteria, subcriteria, subsubcriteria
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('weight', 10, 8);
            $table->decimal('lambda_max', 10, 8)->nullable();
            $table->decimal('ci', 10, 8)->nullable();
            $table->decimal('cr', 10, 8)->nullable();
            $table->boolean('is_consistent')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria_weights');
    }
};
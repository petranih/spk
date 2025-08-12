<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pairwise_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('comparison_type'); // criteria, subcriteria, subsubcriteria
            $table->unsignedBigInteger('parent_id')->nullable(); // for subcriteria and subsubcriteria
            $table->unsignedBigInteger('item_a_id');
            $table->unsignedBigInteger('item_b_id');
            $table->decimal('value', 10, 8);
            $table->timestamps();
            
            $table->unique(['comparison_type', 'parent_id', 'item_a_id', 'item_b_id'], 'unique_comparison');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairwise_comparisons');
    }
};
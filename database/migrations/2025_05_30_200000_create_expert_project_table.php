<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('expert_id')->constrained('experts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'expert_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_project');
    }
};
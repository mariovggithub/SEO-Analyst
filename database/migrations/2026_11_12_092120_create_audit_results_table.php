<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('audit_id')->constrained('audits')->cascadeOnDelete();
            $table->string('category');
            $table->json('raw_data')->nullable();
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['audit_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_results');
    }
};


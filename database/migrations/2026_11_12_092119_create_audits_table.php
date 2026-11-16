<?php

use App\Enums\AuditStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('url');
            $table->string('domain');
            $table->string('status')->default(AuditStatus::Pending->value);
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('created_by');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};

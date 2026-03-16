<?php

use App\Enums\QuickUploadStatus;
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
        Schema::create('quick_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pdf_path');
            $table->unsignedBigInteger('pdf_size')->nullable();
            $table->string('compressed_pdf_path')->nullable();
            $table->unsignedBigInteger('compressed_pdf_size')->nullable();
            $table->enum('status', QuickUploadStatus::values())->default(QuickUploadStatus::Pending->value);
            $table->text('reason')->nullable();
            $table->timestamp('ai_processed_at')->nullable();
            $table->timestamp('manual_review_requested_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manual_reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_uploads');
    }
};

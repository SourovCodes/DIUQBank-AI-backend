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
            $table->enum('status', QuickUploadStatus::values())->default(QuickUploadStatus::Pending->value);
            $table->text('ai_rejection_reason')->nullable();
            $table->timestamp('ai_processed_at')->nullable();
            $table->timestamp('manual_review_requested_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('manual_rejection_reason')->nullable();
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

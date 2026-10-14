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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_code', 50)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('room_id')->nullable()->constrained('meeting_rooms')->nullOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('meeting_type', 30)->default('in_person');
            $table->string('virtual_link', 500)->nullable();
            $table->date('meeting_date')->index();
            $table->time('start_time')->index();
            $table->time('end_time')->index();
            $table->string('status', 30)->default('scheduled')->index();
            $table->text('cancellation_reason')->nullable();
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();

            $table->index(['meeting_date', 'room_id']);
            $table->index(['organizer_id', 'meeting_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};

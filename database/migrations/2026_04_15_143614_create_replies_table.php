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
        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->enum('channel', ['email', 'linkedin', 'instagram'])->default('email');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->string('from_email')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->index('lead_id');
            $table->index('campaign_id');
            $table->index('channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};

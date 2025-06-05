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
        // Schema::create('documents', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['pdf', 'doc']);
            $table->string('filename');
            $table->text('summary')->nullable();
            $table->string('external_app_id')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id'); // ID dari Telegram
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');   // mesej sebenar
            $table->timestamps();      // created_at & updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};

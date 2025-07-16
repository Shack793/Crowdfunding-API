<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_id')->constrained('contributions')->onDelete('cascade');
            $table->enum('type', ['donation', 'withdrawal']);
            $table->string('currency');
            $table->decimal('amount', 12, 2);
            $table->decimal('charge', 12, 2)->default(0);
            $table->enum('status', ['pending', 'successful', 'failed', 'cancelled']);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};

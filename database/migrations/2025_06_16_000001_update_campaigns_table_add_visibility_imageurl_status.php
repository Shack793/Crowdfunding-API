<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('public')->after('status');
            $table->string('image_url')->nullable()->after('thumbnail');
            $table->enum('status', ['draft', 'pending', 'active', 'completed', 'rejected'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('visibility');
            $table->dropColumn('image_url');
            $table->enum('status', ['upcoming', 'running', 'pending', 'expired', 'cancelled', 'completed'])->default('pending')->change();
        });
    }
};

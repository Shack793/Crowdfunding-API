<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('amount');
            $table->string('transaction_status')->default('pending')->after('transaction_id');
        });
    }

    public function down()
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'transaction_status']);
        });
    }
};

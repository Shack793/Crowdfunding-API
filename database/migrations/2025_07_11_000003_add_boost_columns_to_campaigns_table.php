<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_boosted')->default(false)->after('status');
            $table->dateTime('boost_ends_at')->nullable()->after('is_boosted');
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['is_boosted', 'boost_ends_at']);
        });
    }
};

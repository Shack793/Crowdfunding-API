<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
Schema::create('users', function (Blueprint $table) {
$table->id();
$table->string('name');
$table->string('email')->unique();
$table->string('phone')->nullable();
$table->string('country')->nullable();
$table->string('password');
$table->boolean('email_verified')->default(false);
$table->boolean('mobile_verified')->default(false);
$table->enum('role', ['admin', 'institution', 'individual']);
$table->decimal('balance', 12, 2)->default(0);
$table->string('profile_image')->nullable();
$table->timestamps();
});
}

public function down()
{
Schema::dropIfExists('users');
}
};

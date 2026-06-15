<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sf_environments', function (Blueprint $table) {
            $table->id();
            $table->string('persona_key')->unique();
            $table->string('sf_url');
            $table->string('after_login_url');
            $table->string('username');
            $table->text('password');
            $table->text('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sf_environments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
        
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email', 191)->nullable()->index();
        
            $table->string('ip', 64)->nullable()->index();
            $table->string('location', 64)->nullable();  // 归属地
            $table->string('ua', 512)->nullable();        // User-Agent
        
            $table->boolean('success')->default(true)->index();  // 是否登录成功
        
            $table->timestamps();  // created_at = 登录时间
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
}

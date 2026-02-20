<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoginLogsTable extends Migration
{
    public function up()
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();
            $table->string('email', 191)->nullable()->index();

            $table->string('ip', 64)->nullable()->index();
            $table->string('location', 64)->nullable();   // 归属地
            $table->string('ua', 512)->nullable();        // User-Agent

            $table->boolean('success')->default(true)->index(); // 是否登录成功

            // ⭐ 与 v2board 其他表保持一致：INT Unix 时间戳
            $table->unsignedInteger('created_at')->index();
            $table->unsignedInteger('updated_at')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_logs');
    }
}

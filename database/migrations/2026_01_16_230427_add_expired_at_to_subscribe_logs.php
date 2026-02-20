<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpiredAtToSubscribeLogs extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('expired_at')
                ->nullable()
                ->after('user_id');

            // 可选：如果你后续会按过期时间做分析/筛选
            $table->index('expired_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->dropIndex(['expired_at']);
            $table->dropColumn('expired_at');
        });
    }
}

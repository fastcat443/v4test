<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SplitLocationInSubscribeLogs extends Migration
{
    public function up()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            // 新增结构化地理字段
            $table->string('country', 50)->nullable()->after('ip');
            $table->string('region', 50)->nullable()->after('country');
            $table->string('city', 50)->nullable()->after('region');
            $table->string('asn', 32)->nullable()->after('city');
            $table->string('isp', 100)->nullable()->after('asn');
        });

        // 单独处理 drop（避免和 add 混在一个 schema 里）
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->dropColumn(['country', 'region', 'city', 'asn', 'isp']);
            $table->string('location')->nullable()->after('ip');
        });
    }
}

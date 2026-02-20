<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SplitLocationInLoginLogs extends Migration
{
    public function up()
    {
        Schema::table('login_logs', function (Blueprint $table) {
            // ===== 新增 Geo 拆分字段 =====
            $table->string('country', 50)->nullable()->after('host');
            $table->string('region', 50)->nullable()->after('country');
            $table->string('city', 50)->nullable()->after('region');
            $table->unsignedInteger('asn')->nullable()->after('city');
            $table->string('isp', 100)->nullable()->after('asn');

        });

        // ===== 删除旧 location 字段 =====
        Schema::table('login_logs', function (Blueprint $table) {
            if (Schema::hasColumn('login_logs', 'location')) {
                $table->dropColumn('location');
            }
        });
    }

    public function down()
    {
        Schema::table('login_logs', function (Blueprint $table) {
            // 回滚时恢复 location
            $table->string('location', 64)->nullable();

            $table->dropIndex(['country']);
            $table->dropIndex(['asn']);

            $table->dropColumn(['country', 'region', 'city', 'asn', 'isp']);
        });
    }
}

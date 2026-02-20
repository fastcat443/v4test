<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHostToLoginLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->string('host', 255)
                  ->nullable()
                  ->after('ip');

            // 可选：为后续查询/风控预留
            $table->index('host');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropIndex(['host']);
            $table->dropColumn('host');
        });
    }
}

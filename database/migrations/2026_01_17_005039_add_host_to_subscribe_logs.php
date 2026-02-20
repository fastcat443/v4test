<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHostToSubscribeLogs extends Migration
{
    public function up()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->string('host', 191)
                ->nullable()
                ->after('ip')
                ->index();
        });
    }

    public function down()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->dropColumn('host');
        });
    }
}

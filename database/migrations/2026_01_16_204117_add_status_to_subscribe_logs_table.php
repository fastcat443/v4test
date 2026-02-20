<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToSubscribeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('success')
                ->comment('success / invalid / banned / expired')
                ->after('ua');
        });
    }
    
    public function down()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

}

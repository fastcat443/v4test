<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientMetaToSubscribeLogs extends Migration
{
    public function up()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {

            if (!Schema::hasColumn('subscribe_logs', 'ua_hash')) {
                $table->char('ua_hash', 32)
                      ->nullable()
                      ->index()
                      ->after('ua');
            }

            if (!Schema::hasColumn('subscribe_logs', 'client_name')) {
                $table->string('client_name', 100)
                      ->nullable()
                      ->after('client_type');
            }


            if (!Schema::hasColumn('subscribe_logs', 'platform')) {
                $table->string('platform', 20)
                      ->nullable()
                      ->after('client_name');
            }

            if (!Schema::hasColumn('subscribe_logs', 'client_version')) {
                $table->string('client_version', 50)
                      ->nullable()
                      ->after('platform');
            }
        });
    }

    public function down()
    {
        Schema::table('subscribe_logs', function (Blueprint $table) {
            $table->dropColumn([
                'ua_hash',
                'client_name',
                'platform',
                'client_version',
            ]);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscribeLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscribe_logs', function (Blueprint $table) {
            $table->id();
    
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email', 191)->index();
    
            $table->integer('plan_id')->nullable()->index();
            $table->string('plan_name', 191)->nullable();
            
            $table->string('client_type', 32)->nullable()->index();  
            $table->string('ip', 64)->nullable()->index();
            $table->string('location', 64)->nullable();
            $table->string('ua', 512)->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('subscribe_logs');
    }
}

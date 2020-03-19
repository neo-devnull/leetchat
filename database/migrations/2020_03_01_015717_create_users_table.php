<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {            
            $table->integer('socketId')->primary();
            $table->integer('status')->nullable();
            $table->text('interests')->nullable();
            $table->binary('sess_id')->nullable();
        });
        DB::statement('ALTER TABLE users ADD FULLTEXT full(interests)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

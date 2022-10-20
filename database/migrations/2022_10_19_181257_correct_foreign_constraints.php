<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = config('database.default');

        if ($driver !== 'sqlite') {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });

            Schema::table('project_accesses', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropForeign(['user_id']);
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = config('database.default');

        if ($driver !== 'sqlite') {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users');
            });

            Schema::table('project_accesses', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropForeign(['user_id']);
                $table->foreign('project_id')->references('id')->on('projects');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }
};

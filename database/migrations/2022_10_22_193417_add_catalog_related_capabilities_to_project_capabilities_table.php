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
        Schema::table('project_capabilities', function (Blueprint $table) {
            $table->boolean('view_catalog')->default(false);
            $table->boolean('edit_catalog')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_capabilities', function (Blueprint $table) {
            $table->dropColumn('view_catalog');
            $table->dropColumn('edit_catalog');
        });
    }
};

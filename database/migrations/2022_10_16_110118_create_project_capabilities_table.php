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
        Schema::create('project_capabilities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->boolean('manage_project')->default(false);
            $table->boolean('manage_users')->default(false);
            $table->boolean('manage_forms')->default(false);
            $table->boolean('record_data')->default(false);
            $table->boolean('manage_data')->default(false);
            $table->boolean('generate_reports')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_capabilities');
    }
};

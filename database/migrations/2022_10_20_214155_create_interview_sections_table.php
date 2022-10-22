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
        Schema::create('interview_sections', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('interview_form_id');
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->integer('order')->nullable();
            $table->boolean('repeatable')->default(false);
            $table->foreign('interview_form_id')->references('id')->on('interview_forms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interview_sections');
    }
};

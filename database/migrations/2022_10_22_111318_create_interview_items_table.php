<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('interview_section_id');
            $table->string('label')->nullable();
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('order');
            $table->decimal('min', 8, 2)->nullable();
            $table->decimal('max', 8, 2)->nullable();
            $table->decimal('step', 8, 2)->nullable();
            $table->json('options')->nullable();
            $table->boolean('link_to_species')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interview_items');
    }
};

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
        Schema::create('catalog_species_photos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('catalog_species_id');
            $table->string('path');
            $table->string('caption')->nullable();
            $table->string('author')->nullable();
            $table->string('license')->nullable();
            $table->string('license_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('catalog_species_photos');
    }
};

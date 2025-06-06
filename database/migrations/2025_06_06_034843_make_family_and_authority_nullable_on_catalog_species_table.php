<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_species', function (Blueprint $table) {
            $table->string('family')->nullable()->change();
            $table->string('authority')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('catalog_species', function (Blueprint $table) {
            $table->string('family')->nullable(false)->change();
            $table->string('authority')->nullable(false)->change();
        });
    }
};

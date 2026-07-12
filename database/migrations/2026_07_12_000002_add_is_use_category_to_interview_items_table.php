<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks the item whose answer is a use-category (food, medicine, …). Together
     * with the existing link_to_species (the taxon field), this gives the
     * semantic roles the quantitative indices need — see
     * docs/decisions/0007-use-category-as-item-role.md.
     */
    public function up(): void
    {
        Schema::table('interview_items', function (Blueprint $table) {
            $table->boolean('is_use_category')->default(false)->after('link_to_species');
        });
    }

    public function down(): void
    {
        Schema::table('interview_items', function (Blueprint $table) {
            $table->dropColumn('is_use_category');
        });
    }
};

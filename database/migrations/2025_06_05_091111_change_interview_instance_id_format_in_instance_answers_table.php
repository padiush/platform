<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('instance_answers', function (Blueprint $table) {
            // Change the type of interview_instance_id to uuid
            $table->uuid('interview_instance_id')->change()->after('id');

            // Add foreign key constraint for interview_instance_id
            $table
                ->foreign('interview_instance_id')
                ->references('id')
                ->on('interview_instances')
                ->onDelete('cascade');

            // Ensure that the interview_section_id and interview_item_id are not nullable
            $table
                ->unsignedBigInteger('interview_section_id')
                ->nullable(false)
                ->change();
            $table
                ->unsignedBigInteger('interview_item_id')
                ->nullable(false)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instance_answers', function (Blueprint $table) {
            // Revert the interview_instance_id to unsignedBigInteger
            $table->unsignedBigInteger('interview_instance_id')->change();

            // Drop the foreign key constraint
            $table->dropForeign(['interview_instance_id']);

            // Revert interview_section_id and interview_item_id to nullable
            $table
                ->unsignedBigInteger('interview_section_id')
                ->nullable()
                ->change();
            $table
                ->unsignedBigInteger('interview_item_id')
                ->nullable()
                ->change();
        });
    }
};

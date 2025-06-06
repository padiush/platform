<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\confirm;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ask the user to confirm the migration
        if (!app()->runningInConsole()) {
            throw new \Exception(
                'This migration is intended to be run in console only.'
            );
        }

        // Confirm with the user before proceeding, this will drop and recreate the table
        if (
            !confirm(
                "This migration will drop and recreate the 'interview_instances' table, truncating all data. Are you sure you want to proceed? (yes/no)"
            )
        ) {
            return;
        }

        // Truncate the table before changing the primary key
        DB::table('interview_instances')->truncate();

        Schema::dropIfExists('interview_instances');

        Schema::create('interview_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->unsignedBigInteger('interview_form_id');
            $table->unsignedBigInteger('user_id')->nullable();

            $table
                ->foreign('interview_form_id')
                ->references('id')
                ->on('interview_forms')
                ->onDelete('cascade');

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_instances');

        Schema::create('interview_instances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('interview_form_id');
            $table->unsignedBigInteger('user_id')->nullable();

            $table
                ->foreign('interview_form_id')
                ->references('id')
                ->on('interview_forms')
                ->onDelete('cascade');

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};

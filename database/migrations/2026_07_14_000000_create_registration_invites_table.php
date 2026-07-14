<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_invites', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('inviting_user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('invited_name');
            $table->string('invited_email')->unique();
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_invites');
    }
};

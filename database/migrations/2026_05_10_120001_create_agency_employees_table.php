<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agency_employees')) {
            return;
        }

        Schema::create('agency_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('position', 120)->nullable();
            $table->string('status', 30)->default('active');
            $table->boolean('can_login')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['position', 'status']);
            $table->unique(['branch_id', 'email'], 'agency_employees_branch_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_employees');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            return;
        }
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->string('type', 30)->default('branch'); // head_office, branch
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 190)->nullable();
            $table->unsignedBigInteger('manager_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

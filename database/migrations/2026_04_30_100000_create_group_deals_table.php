<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('destination')->nullable();
            $table->longText('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('min_participants')->default(1);
            $table->unsignedInteger('max_participants')->default(20);
            $table->unsignedInteger('current_participants')->default(0);
            $table->string('status', 32)->default('draft');
            $table->date('registration_deadline')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->longText('program')->nullable();
            $table->json('services_included')->nullable();
            $table->json('services_excluded')->nullable();
            $table->boolean('share_enabled')->default(true);
            $table->decimal('current_price', 12, 2)->nullable();
            $table->timestamp('guaranteed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'start_date']);
            $table->index('registration_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_deals');
    }
};

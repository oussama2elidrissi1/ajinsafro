<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_labels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('color', 20)->default('primary');
            $table->timestamps();
        });

        if (Schema::hasTable('reservation_messages')) {
            Schema::table('reservation_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('reservation_messages', 'status')) {
                    $table->string('status', 20)->default('sent')->after('body');
                }
                if (!Schema::hasColumn('reservation_messages', 'is_important')) {
                    $table->boolean('is_important')->default(false)->after('status');
                }
                if (!Schema::hasColumn('reservation_messages', 'label_id')) {
                    $table->unsignedBigInteger('label_id')->nullable()->after('is_important');
                    $table->foreign('label_id')->references('id')->on('message_labels')->onDelete('set null');
                }
            });
        }

        Schema::create('message_stars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('message_id');
            $table->timestamps();
            $table->unique(['user_id', 'message_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('reservation_messages')->onDelete('cascade');
        });

        $this->seedDefaultLabels();
    }

    private function seedDefaultLabels(): void
    {
        $labels = [
            ['name' => 'Support thème', 'color' => 'info'],
            ['name' => 'Freelance', 'color' => 'warning'],
            ['name' => 'Social', 'color' => 'primary'],
            ['name' => 'Amis', 'color' => 'danger'],
            ['name' => 'Famille', 'color' => 'success'],
        ];
        foreach ($labels as $l) {
            \DB::table('message_labels')->insert(array_merge($l, ['created_at' => now(), 'updated_at' => now()]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_stars');
        if (Schema::hasTable('reservation_messages') && Schema::hasColumn('reservation_messages', 'label_id')) {
            Schema::table('reservation_messages', function (Blueprint $table) {
                $table->dropForeign(['label_id']);
            });
        }
        if (Schema::hasTable('reservation_messages')) {
            Schema::table('reservation_messages', function (Blueprint $table) {
                $table->dropColumn(['status', 'is_important', 'label_id']);
            });
        }
        Schema::dropIfExists('message_labels');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
                $table->string('subject');
                $table->text('body');
                $table->text('preview')->nullable();
                $table->boolean('read')->default(false);
                $table->boolean('starred')->default(false);
                $table->enum('folder_sender', ['sent', 'trash'])->nullable();
                $table->enum('folder_recipient', ['inbox', 'trash'])->default('inbox');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['recipient_id', 'folder_recipient', 'read']);
                $table->index(['sender_id', 'folder_sender']);
            });

            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'sender_id')) {
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete()->after('id');
            }
            if (! Schema::hasColumn('messages', 'recipient_id')) {
                $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete()->after('sender_id');
            }
            if (! Schema::hasColumn('messages', 'subject')) {
                $table->string('subject')->after('recipient_id');
            }
            if (! Schema::hasColumn('messages', 'body')) {
                $table->text('body')->after('subject');
            }
            if (! Schema::hasColumn('messages', 'preview')) {
                $table->text('preview')->nullable()->after('body');
            }
            if (! Schema::hasColumn('messages', 'read')) {
                $table->boolean('read')->default(false)->after('preview');
            }
            if (! Schema::hasColumn('messages', 'starred')) {
                $table->boolean('starred')->default(false)->after('read');
            }
            if (! Schema::hasColumn('messages', 'folder_sender')) {
                $table->enum('folder_sender', ['sent', 'trash'])->nullable()->after('starred');
            }
            if (! Schema::hasColumn('messages', 'folder_recipient')) {
                $table->enum('folder_recipient', ['inbox', 'trash'])->default('inbox')->after('folder_sender');
            }
            if (! Schema::hasColumn('messages', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('folder_recipient');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->index(['recipient_id', 'folder_recipient', 'read'], 'messages_recipient_folder_read_idx');
            $table->index(['sender_id', 'folder_sender'], 'messages_sender_folder_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('partners')) {
            return;
        }

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('raison_sociale');
            $table->string('nom_commercial')->nullable();
            $table->string('nom_responsable');
            $table->string('email')->unique();
            $table->string('telephone', 50);
            $table->string('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('code_postal', 20)->nullable();
            $table->string('pays', 100)->nullable();

            $table->string('ice', 50)->nullable()->comment('Identifiant Commun Entreprise');
            $table->string('if', 50)->nullable()->comment('Identifiant Fiscal');
            $table->string('rc', 50)->nullable()->comment('Registre de Commerce');
            $table->string('document_path', 500)->nullable()->comment('Pièce justificative');

            $table->string('status', 30)->default('pending'); // pending, validated, rejected, suspended
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejected_reason', 500)->nullable();

            $table->timestamps();

            $table->index('status');
            $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};

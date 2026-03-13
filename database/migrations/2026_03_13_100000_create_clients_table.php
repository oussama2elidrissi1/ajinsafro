<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('client_code')->unique();
            $table->enum('client_type', ['individual', 'company', 'agency'])->default('individual');
            $table->enum('status', ['active', 'inactive', 'blocked', 'vip'])->default('active');
            $table->string('source', 50)->nullable(); // website, whatsapp, phone, facebook, instagram, referral, walkin, admin
            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('country_of_residence', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('preferred_language', 10)->default('fr');

            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('phone_alt', 50)->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->string('website')->nullable();
            $table->enum('contact_method_preference', ['phone', 'email', 'whatsapp'])->nullable();

            $table->string('passport_number', 50)->nullable();
            $table->string('passport_issue_country', 100)->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('national_id_number', 50)->nullable();
            $table->boolean('visa_required')->default(false);
            $table->enum('visa_status', ['not_required', 'pending', 'approved', 'rejected'])->default('not_required');

            $table->enum('traveler_category', ['solo', 'couple', 'family', 'group', 'business'])->nullable();
            $table->string('preferred_departure_city', 100)->nullable();
            $table->string('preferred_destination')->nullable();
            $table->string('preferred_travel_month', 50)->nullable();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->json('travel_interests')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('dietary_requirements')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();

            $table->string('company_name')->nullable();
            $table->string('company_registration_number', 100)->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('company_contact_person')->nullable();

            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone', 50)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_country', 100)->nullable();
            $table->string('billing_postal_code', 20)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('credit_limit', 12, 2)->nullable();

            $table->boolean('newsletter_opt_in')->default(false);
            $table->boolean('sms_opt_in')->default(false);
            $table->boolean('whatsapp_opt_in')->default(false);
            $table->integer('loyalty_points')->default(0);
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();

            $table->string('avatar')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->text('blacklist_reason')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->unsignedBigInteger('merged_into_client_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->index('email');
            $table->index('phone');
            $table->index('passport_number');
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->foreign('merged_into_client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};

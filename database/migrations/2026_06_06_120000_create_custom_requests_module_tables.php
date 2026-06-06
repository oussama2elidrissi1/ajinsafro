<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique()->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_full_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_country')->nullable();
            $table->string('customer_identity')->nullable();
            $table->enum('customer_type', ['new_customer', 'existing_customer'])->default('new_customer');
            $table->text('customer_notes')->nullable();
            $table->string('desired_destination');
            $table->string('departure_city');
            $table->date('desired_departure_date');
            $table->date('desired_return_date')->nullable();
            $table->string('desired_duration')->nullable();
            $table->enum('travel_type', ['organized_trip', 'omra', 'hajj', 'hotel_stay', 'flight_ticket', 'circuit', 'visa', 'transport', 'other']);
            $table->unsignedInteger('travelers_count');
            $table->unsignedInteger('adults_count');
            $table->unsignedInteger('children_count')->default(0);
            $table->unsignedInteger('babies_count')->default(0);
            $table->decimal('approximate_budget', 12, 2)->nullable();
            $table->enum('currency', ['MAD', 'EUR', 'USD'])->default('MAD');
            $table->enum('desired_level', ['economy', 'standard', 'comfort', 'premium', 'luxury'])->nullable();
            $table->string('desired_hotel')->nullable();
            $table->enum('hotel_category', ['3_stars', '4_stars', '5_stars', 'riad', 'apartment', 'villa', 'unspecified'])->nullable();
            $table->enum('meal_plan', ['room_only', 'breakfast', 'half_board', 'full_board', 'all_inclusive'])->nullable();
            $table->unsignedInteger('rooms_count')->nullable();
            $table->enum('room_type', ['single', 'double', 'triple', 'quadruple', 'family'])->nullable();
            $table->boolean('separate_room_needed')->nullable();
            $table->text('accommodation_notes')->nullable();
            $table->enum('flight_included', ['yes', 'no', 'to_confirm'])->nullable();
            $table->string('preferred_airline')->nullable();
            $table->string('departure_airport')->nullable();
            $table->string('arrival_airport')->nullable();
            $table->enum('baggage_included', ['yes', 'no', 'to_confirm'])->nullable();
            $table->enum('airport_transfer_included', ['yes', 'no'])->nullable();
            $table->enum('local_transport', ['none', 'bus', 'minibus', 'private_car', 'private_driver'])->nullable();
            $table->text('transport_notes')->nullable();
            $table->text('requested_services_details')->nullable();
            $table->decimal('estimated_price', 12, 2)->nullable();
            $table->decimal('requested_deposit', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->nullable();
            $table->enum('payment_method', ['cash', 'transfer', 'card', 'cheque', 'other'])->nullable();
            $table->enum('payment_status', ['unpaid', 'deposit_paid', 'partially_paid', 'fully_paid'])->default('unpaid');
            $table->enum('status', ['draft', 'new', 'assigned', 'processing', 'missing_info', 'modification_requested', 'quote_prepared', 'quote_sent', 'waiting_customer', 'confirmed', 'cancelled', 'refused'])->default('draft')->index();
            $table->enum('priority', ['normal', 'urgent', 'very_urgent'])->default('normal')->index();
            $table->date('response_deadline')->nullable();
            $table->text('internal_notes')->nullable();
            $table->dateTime('quote_sent_at')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_by', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['desired_destination', 'desired_departure_date']);
        });

        Schema::create('custom_request_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_id')->constrained('custom_requests')->cascadeOnDelete();
            $table->enum('service_key', ['visa', 'travel_insurance', 'tourist_guide', 'excursions', 'activities', 'transfers', 'flight_ticket', 'hotel', 'car_rental', 'catering', 'group_assistance', 'other']);
            $table->string('service_label');
            $table->timestamps();
        });

        Schema::create('custom_request_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_id')->constrained('custom_requests')->cascadeOnDelete();
            $table->string('quote_number', 40)->unique();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['draft', 'prepared', 'sent', 'modification_requested', 'accepted', 'refused'])->default('draft')->index();
            $table->enum('currency', ['MAD', 'EUR', 'USD'])->default('MAD');
            $table->string('supplier_name')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('total_purchase', 12, 2)->default(0);
            $table->decimal('total_margin', 12, 2)->default(0);
            $table->decimal('total_sale', 12, 2)->default(0);
            $table->decimal('requested_deposit', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remaining_amount', 12, 2)->nullable();
            $table->text('customer_conditions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->dateTime('prepared_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['custom_request_id', 'version']);
        });

        Schema::create('custom_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_id')->constrained('custom_requests')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('custom_request_quotes')->nullOnDelete();
            $table->enum('document_type', ['identity', 'payment_receipt', 'quote', 'tickets', 'hotel_voucher', 'supplier_file', 'other'])->default('other')->index();
            $table->string('title')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
        });

        Schema::create('custom_request_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_quote_id')->constrained('custom_request_quotes')->cascadeOnDelete();
            $table->enum('service_type', ['hotel', 'flight', 'transfer', 'visa', 'insurance', 'activity', 'transport', 'other']);
            $table->text('description');
            $table->string('supplier_name')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_purchase_price', 12, 2)->default(0);
            $table->decimal('unit_margin', 12, 2)->default(0);
            $table->decimal('unit_sale_price', 12, 2)->default(0);
            $table->decimal('total_purchase', 12, 2)->default(0);
            $table->decimal('total_margin', 12, 2)->default(0);
            $table->decimal('total_sale', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_request_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_id')->constrained('custom_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('comment_type', ['internal', 'agent_message', 'offline_message', 'modification_request', 'missing_info'])->default('internal')->index();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('custom_request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_request_id')->constrained('custom_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_request_status_logs');
        Schema::dropIfExists('custom_request_comments');
        Schema::dropIfExists('custom_request_quote_items');
        Schema::dropIfExists('custom_request_documents');
        Schema::dropIfExists('custom_request_quotes');
        Schema::dropIfExists('custom_request_services');
        Schema::dropIfExists('custom_requests');
    }
};

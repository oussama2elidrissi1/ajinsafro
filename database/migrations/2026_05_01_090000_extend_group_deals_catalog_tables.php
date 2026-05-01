<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_deals', function (Blueprint $table) {
            if (! Schema::hasColumn('group_deals', 'country')) {
                $table->string('country')->nullable()->after('destination');
            }

            if (! Schema::hasColumn('group_deals', 'city')) {
                $table->string('city')->nullable()->after('country');
            }

            if (! Schema::hasColumn('group_deals', 'short_description')) {
                $table->text('short_description')->nullable()->after('description');
            }

            if (! Schema::hasColumn('group_deals', 'duration_days')) {
                $table->unsignedInteger('duration_days')->nullable()->after('image');
            }

            if (! Schema::hasColumn('group_deals', 'duration_nights')) {
                $table->unsignedInteger('duration_nights')->nullable()->after('duration_days');
            }

            if (! Schema::hasColumn('group_deals', 'starting_price')) {
                $table->decimal('starting_price', 12, 2)->nullable()->after('current_participants');
            }

            if (! Schema::hasColumn('group_deals', 'discount_percent')) {
                $table->unsignedInteger('discount_percent')->default(0)->after('current_price');
            }

            if (! Schema::hasColumn('group_deals', 'badge_label')) {
                $table->string('badge_label', 120)->nullable()->after('status');
            }

            if (! Schema::hasColumn('group_deals', 'departure_date')) {
                $table->date('departure_date')->nullable()->after('badge_label');
            }

            if (! Schema::hasColumn('group_deals', 'return_date')) {
                $table->date('return_date')->nullable()->after('departure_date');
            }

            if (! Schema::hasColumn('group_deals', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('registration_deadline');
            }

            if (! Schema::hasColumn('group_deals', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_featured');
            }

            if (! Schema::hasColumn('group_deals', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            }
        });

        DB::table('group_deals')
            ->whereNull('departure_date')
            ->update(['departure_date' => DB::raw('start_date')]);

        DB::table('group_deals')
            ->whereNull('return_date')
            ->update(['return_date' => DB::raw('end_date')]);

        DB::table('group_deals')
            ->whereNull('starting_price')
            ->update(['starting_price' => DB::raw('current_price')]);

        if (Schema::hasColumn('group_deals', 'description') && Schema::hasColumn('group_deals', 'short_description')) {
            DB::table('group_deals')
                ->whereNull('short_description')
                ->whereNotNull('description')
                ->update(['short_description' => DB::raw('description')]);
        }

        Schema::table('group_deal_pricing_tiers', function (Blueprint $table) {
            if (! Schema::hasColumn('group_deal_pricing_tiers', 'min_people')) {
                $table->unsignedInteger('min_people')->nullable()->after('min_participants');
            }
        });

        DB::table('group_deal_pricing_tiers')
            ->whereNull('min_people')
            ->update(['min_people' => DB::raw('min_participants')]);

        if (! Schema::hasTable('group_deal_services')) {
            Schema::create('group_deal_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_deal_id')->constrained('group_deals')->cascadeOnDelete();
                $table->string('name');
                $table->string('type', 32);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['group_deal_id', 'type', 'sort_order']);
            });
        }

        if (! Schema::hasTable('group_deal_categories')) {
            Schema::create('group_deal_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('group_deal_category_group_deal')) {
            Schema::create('group_deal_category_group_deal', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_deal_id')->constrained('group_deals')->cascadeOnDelete();
                $table->foreignId('group_deal_category_id')->constrained('group_deal_categories')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['group_deal_id', 'group_deal_category_id'], 'gd_category_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('group_deal_category_group_deal')) {
            Schema::dropIfExists('group_deal_category_group_deal');
        }

        if (Schema::hasTable('group_deal_categories')) {
            Schema::dropIfExists('group_deal_categories');
        }

        if (Schema::hasTable('group_deal_services')) {
            Schema::dropIfExists('group_deal_services');
        }

        Schema::table('group_deal_pricing_tiers', function (Blueprint $table) {
            if (Schema::hasColumn('group_deal_pricing_tiers', 'min_people')) {
                $table->dropColumn('min_people');
            }
        });

        Schema::table('group_deals', function (Blueprint $table) {
            $columns = [
                'country',
                'city',
                'short_description',
                'duration_days',
                'duration_nights',
                'starting_price',
                'discount_percent',
                'badge_label',
                'departure_date',
                'return_date',
                'is_featured',
                'is_active',
                'sort_order',
            ];

            $drops = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('group_deals', $column)));
            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};

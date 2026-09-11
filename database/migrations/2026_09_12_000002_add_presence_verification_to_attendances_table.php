<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('time_in');
            }
            if (!Schema::hasColumn('attendances', 'last_location_check_at')) {
                $table->timestamp('last_location_check_at')->nullable()->after('checked_in_at');
            }
            if (!Schema::hasColumn('attendances', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 8)->nullable()->after('last_location_check_at');
            }
            if (!Schema::hasColumn('attendances', 'last_longitude')) {
                $table->decimal('last_longitude', 11, 8)->nullable()->after('last_latitude');
            }
            if (!Schema::hasColumn('attendances', 'last_accuracy')) {
                $table->decimal('last_accuracy', 8, 2)->nullable()->after('last_longitude');
            }
            if (!Schema::hasColumn('attendances', 'last_distance_meters')) {
                $table->decimal('last_distance_meters', 8, 2)->nullable()->after('last_accuracy');
            }
            if (!Schema::hasColumn('attendances', 'outside_since')) {
                $table->timestamp('outside_since')->nullable()->after('last_distance_meters');
            }
            if (!Schema::hasColumn('attendances', 'consecutive_outside_count')) {
                $table->unsignedSmallInteger('consecutive_outside_count')->default(0)->after('outside_since');
            }
            if (!Schema::hasColumn('attendances', 'escaped_at')) {
                $table->timestamp('escaped_at')->nullable()->after('consecutive_outside_count');
            }
            if (!Schema::hasColumn('attendances', 'monitoring_status')) {
                $table->string('monitoring_status', 50)->default('active')->index()->after('escaped_at');
            }
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_sessions', 'radius_meters')) {
                $table->unsignedInteger('radius_meters')->nullable()->default(50)->after('classroom_lng');
            }
            if (!Schema::hasColumn('attendance_sessions', 'grace_period_minutes')) {
                $table->unsignedSmallInteger('grace_period_minutes')->nullable()->default(5)->after('radius_meters');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $cols = [
                'checked_in_at',
                'last_location_check_at',
                'last_latitude',
                'last_longitude',
                'last_accuracy',
                'last_distance_meters',
                'outside_since',
                'consecutive_outside_count',
                'escaped_at',
                'monitoring_status',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('attendances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_sessions', 'radius_meters')) {
                $table->dropColumn('radius_meters');
            }
            if (Schema::hasColumn('attendance_sessions', 'grace_period_minutes')) {
                $table->dropColumn('grace_period_minutes');
            }
        });
    }
};

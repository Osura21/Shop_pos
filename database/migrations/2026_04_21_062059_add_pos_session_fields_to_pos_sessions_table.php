<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('pos_sessions', 'pos_register_id')) {
                $table->foreignId('pos_register_id')
                    ->nullable()
                    ->after('tenant_id')
                    ->constrained('pos_registers')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('pos_sessions', 'opened_by')) {
                $table->foreignId('opened_by')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('pos_sessions', 'closed_by')) {
                $table->foreignId('closed_by')
                    ->nullable()
                    ->after('opened_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('pos_sessions', 'opening_float')) {
                $table->decimal('opening_float', 14, 3)->default(0)->after('notes');
            }

            if (!Schema::hasColumn('pos_sessions', 'current_balance')) {
                $table->decimal('current_balance', 14, 3)->default(0)->after('opening_float');
            }

            if (!Schema::hasColumn('pos_sessions', 'opened_at')) {
                $table->timestamp('opened_at')->nullable()->after('current_balance');
            }

            if (!Schema::hasColumn('pos_sessions', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('opened_at');
            }

            if (!Schema::hasColumn('pos_sessions', 'closing_note')) {
                $table->text('closing_note')->nullable()->after('closed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            foreach ([
                'pos_register_id',
                'opened_by',
                'closed_by',
                'opening_float',
                'current_balance',
                'opened_at',
                'closed_at',
                'closing_note',
            ] as $column) {
                if (Schema::hasColumn('pos_sessions', $column)) {
                    try {
                        if (in_array($column, ['pos_register_id', 'opened_by', 'closed_by'], true)) {
                            $table->dropForeign([$column]);
                        }
                    } catch (\Throwable $e) {
                    }

                    $table->dropColumn($column);
                }
            }
        });
    }
};
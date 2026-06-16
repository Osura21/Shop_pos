<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->json('branch_ids')->nullable()->after('tenant_id');
        });

        DB::table('ingredients')->get()->each(function ($ingredient) {
            DB::table('ingredients')
                ->where('id', $ingredient->id)
                ->update([
                    'branch_ids' => json_encode([$ingredient->branch_id])
                ]);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->integer('branch_id')->nullable()->after('tenant_id');
        });

        DB::table('ingredients')->get()->each(function ($ingredient) {
            DB::table('ingredients')
                ->where('id', $ingredient->id)
                ->update([
                    'branch_id' => is_array(json_decode($ingredient->branch_ids, true))
                        ? json_decode($ingredient->branch_ids, true)[0] ?? null
                        : null
                ]);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('branch_ids');
        });
    }
};
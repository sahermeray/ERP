<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->text('address')->nullable();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->text('description')->nullable();
        });

        $invalidMembershipForeignKey = collect(Schema::getForeignKeys('organization_user_role'))
            ->contains(fn (array $foreignKey): bool => in_array('organization_user_id', $foreignKey['columns'], true)
                && $foreignKey['foreign_table'] !== 'organization_user');

        if ($invalidMembershipForeignKey) {
            Schema::create('organization_user_role_replacement', function (Blueprint $table) {
                $table->foreignId('organization_user_id')->constrained('organization_user')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->primary(['organization_user_id', 'role_id']);
            });

            DB::table('organization_user_role_replacement')->insertUsing(
                ['organization_user_id', 'role_id', 'assigned_by', 'assigned_at'],
                DB::table('organization_user_role')->select(['organization_user_id', 'role_id', 'assigned_by', 'assigned_at']),
            );

            Schema::drop('organization_user_role');
            Schema::rename('organization_user_role_replacement', 'organization_user_role');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['description', 'address']);
        });
    }
};

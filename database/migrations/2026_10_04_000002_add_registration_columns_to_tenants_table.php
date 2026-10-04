<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('legal_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('person_type', 2)->nullable();
            $table->string('document', 14)->nullable()->unique();
            $table->string('state_registration', 30)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('zip_code', 8)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->restrictOnDelete();
            $table->string('billing_cycle', 20)->nullable();
            $table->string('status', 20)->default('active');
            $table->softDeletes();
        });

        // Tenants anteriores guardavam o nome dentro da coluna JSON `data`.
        DB::table('tenants')->orderBy('id')->each(function (object $tenant): void {
            $data = json_decode((string) $tenant->data, true);
            $name = is_array($data) && isset($data['tenant_name']) ? (string) $data['tenant_name'] : (string) $tenant->id;

            DB::table('tenants')->where('id', $tenant->id)->update(['legal_name' => $name]);
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropUnique(['document']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'legal_name', 'trade_name', 'person_type', 'document', 'state_registration',
                'contact_name', 'contact_email', 'contact_phone',
                'zip_code', 'street', 'number', 'complement', 'district', 'city', 'state',
                'notes', 'billing_cycle', 'status',
            ]);
        });
    }
};

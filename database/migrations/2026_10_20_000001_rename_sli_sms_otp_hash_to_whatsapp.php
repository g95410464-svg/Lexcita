<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('organization.id') !== 'servicios-legales-integrados'
            || !Schema::hasTable('registration_verifications')
            || !Schema::hasColumn('registration_verifications', 'sms_code_hash')
            || Schema::hasColumn('registration_verifications', 'whatsapp_code_hash')) {
            return;
        }

        Schema::table('registration_verifications', function ($table) {
            $table->renameColumn('sms_code_hash', 'whatsapp_code_hash');
        });
    }

    public function down(): void
    {
        if (config('organization.id') !== 'servicios-legales-integrados'
            || !Schema::hasTable('registration_verifications')
            || !Schema::hasColumn('registration_verifications', 'whatsapp_code_hash')
            || Schema::hasColumn('registration_verifications', 'sms_code_hash')) {
            return;
        }

        Schema::table('registration_verifications', function ($table) {
            $table->renameColumn('whatsapp_code_hash', 'sms_code_hash');
        });
    }
};

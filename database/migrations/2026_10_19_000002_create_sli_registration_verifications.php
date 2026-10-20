<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('organization.id') !== 'servicios-legales-integrados') {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }
            if (!Schema::hasColumn('usuarios', 'telefono_verified_at')) {
                $table->timestamp('telefono_verified_at')->nullable();
            }
        });

        Schema::create('registration_verifications', function (Blueprint $table) {
            $table->id();
            $table->char('session_hash', 64)->unique();
            $table->string('email')->index();
            $table->longText('registration_payload');
            $table->string('email_code_hash');
            $table->string('sms_code_hash');
            $table->timestamp('expires_at')->index();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (config('organization.id') !== 'servicios-legales-integrados') {
            return;
        }

        Schema::dropIfExists('registration_verifications');
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'telefono_verified_at')) {
                $table->dropColumn('telefono_verified_at');
            }
            if (Schema::hasColumn('usuarios', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });
    }
};

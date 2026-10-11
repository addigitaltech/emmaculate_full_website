<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->string('gps_location', 60)->nullable();
            $table->boolean('show_admin_link')->default(true);
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('pledge')->nullable();
            $table->text('anthem')->nullable();
            $table->text('identity_closing')->nullable();
        });

        foreach (['news_posts', 'school_events'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->boolean('send_email')->default(true);
                $table->timestamp('notified_at')->nullable();
            });
            // Posts that already exist must never trigger a mass email.
            DB::table($name)->update(['notified_at' => now()]);
        }
    }

    public function down(): void
    {
        foreach (['news_posts', 'school_events'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn(['send_email', 'notified_at']);
            });
        }
        Schema::table('school_settings', function (Blueprint $table): void {
            $table->dropColumn(['gps_location', 'show_admin_link', 'mission', 'vision', 'pledge', 'anthem', 'identity_closing']);
        });
    }
};

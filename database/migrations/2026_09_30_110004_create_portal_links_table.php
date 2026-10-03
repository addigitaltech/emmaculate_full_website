<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_links', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('status')->default('live')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_links');
    }
};

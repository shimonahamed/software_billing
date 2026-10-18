<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_configuration', function (Blueprint $table) {
            $table->id();
            $table->string('api_url', 500)->nullable();
            $table->text('api_token')->nullable();
            $table->unsignedInteger('fetch_interval')->default(60)
                ->comment('API data fetch interval in minutes');
            $table->timestamp('last_fetch_at')->nullable();
            $table->unsignedTinyInteger('status')->default(1)
                ->comment('0=Inactive, 1=Active');
            $table->json('additional_config')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_configuration');
    }
};
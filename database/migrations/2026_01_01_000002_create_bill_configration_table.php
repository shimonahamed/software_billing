<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_configration', function (Blueprint $table) {
            $table->id();
            $table->string('currency')->default('BDT');
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->boolean('monthly_billing')->default(true);
            $table->boolean('yearly_billing')->default(true);
            $table->boolean('one_time_billing')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_configration');
    }
};
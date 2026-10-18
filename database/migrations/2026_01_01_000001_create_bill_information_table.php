<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_information', function (Blueprint $table) {
            $table->id();
            $table->string('bill_title')->nullable();
            $table->string('bill_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('bill_date')->nullable();
            $table->date('due_date')->nullable();
            $table->tinyInteger('status')->default(0)
                ->comment('0=Unpaid, 1=Partial, 2=Paid');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_information');
    }
};
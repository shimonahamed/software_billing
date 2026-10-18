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
            $table->unsignedTinyInteger('bill_type')->nullable()->comment('1=Monthly, 2=Yearly, 3=One-time');
            $table->string('invoice_no', 255)->nullable();
            $table->string('bill_month', 255)->nullable();
            $table->date('generate_date')->nullable();
            $table->date('billing_start_date')->nullable();
            $table->date('billing_end_date')->nullable();
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('due_amount', 12, 2)->default(0.00);
            $table->decimal('bill_amount', 12, 2)->default(0.00);
            $table->decimal('due_bill_amount', 12, 2)->default(0.00);
            $table->decimal('total_bill_amount', 12, 2)->default(0.00);
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->comment('0=Unpaid, 1=Partial, 2=Paid');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_information');
    }
};
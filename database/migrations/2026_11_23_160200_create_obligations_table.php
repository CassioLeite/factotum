<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->date('due_on');
            $table->string('payment_status')->default('pending')->index();
            $table->timestamps();

            $table->index('due_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obligations');
    }
};

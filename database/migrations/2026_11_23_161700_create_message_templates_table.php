<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('body');
            $table->string('status')->default('active')->index();
            $table->boolean('is_default')->default(false);
            $table->string('meta_template_name')->nullable();
            $table->timestamps();
        });

        DB::table('message_templates')->insert([
            'name' => 'Lembrete de vencimento',
            'body' => 'Olá, {{nome}}. Lembramos que {{obrigacao}} vence em {{vencimento}}.',
            'status' => 'active',
            'is_default' => true,
            'meta_template_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->unique()->constrained('employees', 'employee_id')->nullOnDelete();
            $table->string('username', 50)->unique();
            // Nama tampilan untuk akun HR/admin yang tidak terhubung ke profil karyawan.
            $table->string('display_name', 150)->nullable();
            $table->string('role', 20);
            $table->string('password_hash');
            $table->boolean('must_change_password')->default(true);
            $table->string('account_status', 20)->default('Aktif');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('user_accounts');
    }
};

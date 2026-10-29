<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('employee_id');
            $table->string('nik', 30)->unique();
            $table->string('nama', 150)->index();
            $table->string('nama_panggilan', 100)->nullable();
            $table->string('jenis_kelamin', 20);
            $table->string('kode_jbt', 50)->nullable();
            $table->string('jobtitle', 150)->index();
            $table->string('kategori_karyawan', 100)->nullable();
            $table->string('status_awal', 100)->nullable();
            $table->date('tgl_masuk');
            $table->string('kontrak_ke', 30)->nullable();
            $table->date('akhir_kontrak')->nullable()->index();
            $table->string('status_karyawan', 20)->default('Aktif')->index();
            $table->date('tgl_lahir')->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->string('kewarganegaraan', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('gol_darah', 20)->nullable();
            $table->unsignedSmallInteger('tinggi_badan_cm')->nullable();
            $table->decimal('berat_badan_kg', 5, 1)->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('no_ktp', 32)->nullable();
            $table->string('no_bpjs_tk', 40)->nullable();
            $table->string('status_nikah', 30)->nullable();
            $table->string('npwp', 32)->nullable();
            $table->string('no_bpjs_kes', 40)->nullable();
            $table->string('kk', 32)->nullable();
            $table->string('nama_ibu', 150)->nullable();
            $table->string('status_ptkp', 10)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('foto_path')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->string('level', 20);
            $table->string('major', 150)->nullable();
            $table->string('institution', 200);
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->string('language_name', 100);
            $table->timestamp('created_at')->nullable();
            $table->unique(['employee_id', 'language_name']);
        });

        Schema::create('training_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->string('item_type', 20);
            $table->string('item_name', 200);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('organizer', 200)->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->date('assignment_start')->nullable();
            $table->date('assignment_end')->nullable();
            $table->string('assignment_status', 20)->default('Aktif');
            $table->timestamps();
            $table->index(['project_id', 'assignment_status']);
            $table->index(['employee_id', 'assignment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_projects');
        Schema::dropIfExists('training_certifications');
        Schema::dropIfExists('employee_languages');
        Schema::dropIfExists('employee_education');
        Schema::dropIfExists('employees');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->cascadeOnDelete();
            $table->string('experience_type', 30);
            $table->string('company_name', 200);
            $table->string('project_name', 200)->nullable();
            $table->string('position', 150);
            $table->unsignedTinyInteger('start_month');
            $table->unsignedSmallInteger('start_year');
            $table->unsignedTinyInteger('end_month')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('responsibilities')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user_accounts')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('user_accounts')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_experiences');
    }
};

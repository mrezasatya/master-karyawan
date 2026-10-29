<?php

namespace Tests\Feature;

use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExperienceValidationTest extends TestCase
{
    use RefreshDatabase;

    private function base(array $o = []): array
    {
        return array_merge(['experience_type' => 'Selama bekerja', 'company_name' => 'PT Bukaka', 'position' => 'SPV', 'start_month' => 5, 'start_year' => 2022], $o);
    }

    public function test_finished_experience_requires_end_period_after_start(): void
    {
        $user = $this->employeeUser();
        $this->actingAs($user)->postJson('/pengalaman', $this->base())->assertJsonValidationErrors(['end_month', 'end_year']);
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['end_month' => 1, 'end_year' => 2022]))->assertJsonValidationErrors('end_year');
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['start_month' => 13, 'end_month' => 1, 'end_year' => 2023]))->assertJsonValidationErrors('start_month');
        $this->assertSame(0, WorkExperience::count());
    }

    public function test_current_experience_must_not_have_end_period(): void
    {
        $user = $this->employeeUser();
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['is_current' => 1, 'end_month' => 1, 'end_year' => 2024]))->assertJsonValidationErrors(['end_month', 'end_year']);
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['is_current' => 1]))->assertOk();
        $this->assertTrue(WorkExperience::first()->is_current);
    }

    public function test_future_year_requires_confirmation(): void
    {
        $user = $this->employeeUser();
        $next = now()->year + 1;
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['start_year' => $next, 'is_current' => 1]))->assertJsonValidationErrors('start_year');
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['start_year' => $next, 'is_current' => 1, 'confirm_future' => 1]))->assertOk();
    }

    public function test_archive_is_soft_delete(): void
    {
        $user = $this->employeeUser();
        $this->actingAs($user)->postJson('/pengalaman', $this->base(['is_current' => 1]))->assertOk();
        $exp = WorkExperience::first();
        $this->actingAs($user)->deleteJson('/pengalaman/'.$exp->id)->assertOk();
        $this->assertSoftDeleted($exp);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'work_experience', 'entity_id' => $exp->id, 'action' => 'SoftDelete']);
    }
}

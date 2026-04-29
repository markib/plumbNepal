<?php

namespace Tests\Feature;

use App\Models\AiDiagnosis;
use App\Services\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_analysis_saves_valid_result()
    {
        $mockResult = [
            'issue_type' => 'pipe_leak',
            'urgency' => 'high',
            'estimated_price_min' => 500,
            'estimated_price_max' => 1500,
            'recommended_service' => 'Leak Repair',
            'confidence' => 0.9,
            'summary' => 'Kitchen pipe is leaking.'
        ];

        // Mock the AiService
        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andReturn($mockResult);

        $this->app->instance(AiService::class, $aiService);

        $response = $this->postJson('/api/v1/ai/diagnose', [
               'message' => 'Pipe leaking under kitchen sink'
        ]);

         $response->assertStatus(200)
             ->assertJsonFragment([
                 'issue_type' => 'pipe_leak'
             ]);

        // $this->assertDatabaseHas('ai_diagnoses', [
        //     'issue_type' => 'pipe_leak',
        //     'confidence' => 0.9
        // ]);
    }

    public function test_ai_analysis_returns_error_on_failure()
    {
        $aiService = Mockery::mock(AiService::class);
        $aiService->shouldReceive('analyze')
            ->once()
            ->andThrow(new \Exception('Service unreachable'));

        $this->app->instance(AiService::class, $aiService);

        $response = $this->postJson('/api/v1/ai/diagnose', [
            'message' => 'Pipe leaking under kitchen sink'
             ]);

        $response->assertStatus(502)
            ->assertJsonFragment([
                'status' => 'error',
                'message' => 'Service unreachable'
            ]);
    }
}

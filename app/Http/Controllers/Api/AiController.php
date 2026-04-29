<?php
// app/Http/Controllers/Api/AiController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiagnoseRequest;
use App\Services\AiService;
use App\Services\AiStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function __construct(protected AiService $aiService) {}

    /**
     * Handles the AI Plumbing Diagnosis flow.
     * 1. Calls local Ollama via AiService.
     * 2. Validates AI Confidence.
     * 3. Persists successful hits for marketplace analytics.
     * 4. Returns JSON for the React frontend.
     */
    public function diagnose(DiagnoseRequest $request, AiStorageService $storageService): JsonResponse
    {
        try {
            $analysis = $this->aiService->analyze($request->validated('message'));

            // 2. Extract confidence for the guardrail check
            $confidence = (float) ($analysis['confidence'] ?? 0);
            
            $confidenceThreshold = 0.4;

            // Silently store the result for analytics/history
            if ($confidence >= 0.4) {
                try {
                    $storageService->saveResult([
                        'issue_type'     => $analysis['issue_type'] ?? 'General Plumbing',
                        'urgency'        => $analysis['urgency'] ?? 'Standard',
                        'price_min'      => $analysis['estimated_price_min'] ?? 0,
                        'price_max'      => $analysis['estimated_price_max'] ?? 0,
                        'service'        => $analysis['recommended_service'] ?? 'Consultation',
                        'confidence'     => $confidence,
                        'summary'        => $analysis['summary'] ?? '',
                        'raw'            => json_encode($analysis),
                        'model'          => 'qwen2.5:3b-ollama',
                        'prompt_version' => 'v1.0',
                    ]);
                } catch (\Exception $storageEx) {
                    // Log storage failure but don't stop the user's flow
                    Log::error("AI Storage Failed: " . $storageEx->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 502); // Bad Gateway - appropriate for downstream AI service failure
        }
    }
}

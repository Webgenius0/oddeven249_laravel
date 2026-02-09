<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InteractionService;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class InteractionController extends Controller
{
    use ApiResponse;
    // InteractionController.php

    protected $interactionService;

    public function __construct(InteractionService $interactionService)
    {
        $this->interactionService = $interactionService;
    }

    public function handle(Request $request)
    {
        $request->validate([
            'target_id'        => 'required|integer',
            'target_type'      => 'required|in:portfolio,ad',
            'interaction_type' => 'required|in:like,view,click',
        ]);

        try {
            $result = $this->interactionService->toggleInteraction(
                auth('sanctum')->user(),
                $request->target_id,
                $request->target_type,
                $request->interaction_type
            );
            return $this->success(null, $result['message']);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), $e->getCode() ?: 500);
        }
    }
}

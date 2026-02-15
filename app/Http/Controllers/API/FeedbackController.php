<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'    => 'required|string|max:100', // ex: bug, suggestion, general
            'message' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $feedback = Feedback::create([
                'user_id' => auth()->id(),
                'type'    => $request->type,
                'message' => $request->message,
                'status'  => 'pending',
            ]);

            return $this->success($feedback, 'Thank you for your feedback!', 201);
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }

    public function index()
    {
        try {
            $feedbacks = Feedback::with('user:id,name,email')
                ->where('user_id', auth()->id())
                ->latest()
                ->get();

            return $this->success($feedbacks, 'Feedbacks retrieved successfully!');
        } catch (\Exception $e) {
            return $this->error(null, $e->getMessage(), 500);
        }
    }
}

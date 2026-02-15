<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class FeedbackController extends Controller
{
    /**
     * Display a listing of feedback.
     */
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $data = Feedback::with('user:id,name')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', function ($data) {
                    return $data->user->name ?? 'N/A';
                })
                ->addColumn('status', function ($data) {
                    $checked = $data->status == "resolved" ? "checked" : "";
                    return '
                        <div class="form-check form-switch d-flex">
                            <input onclick="showStatusChangeAlert(' . $data->id . ')"
                                   type="checkbox"
                                   class="form-check-input status-toggle"
                                   id="switch' . $data->id . '"
                                   data-id="' . $data->id . '"
                                   name="status" ' . $checked . '>
                        </div>';
                })
                ->addColumn('message', function ($data) {
                    return Str::limit($data->message, 50, '...'); // ৫০ ক্যারেক্টার দেখাবে
                })
               ->addColumn('action', function ($data) {
                   return '<div class="btn-group btn-group-sm" role="group">
                        <a href="' . route('admin.feedback.show', $data->id) . '" class="text-white btn btn-info" title="View Details">
                            <i class="fa fa-eye"></i>
                        </a>
                        <button type="button" onclick="deleteFeedback(' . $data->id . ')" class="text-white btn btn-danger" title="Delete">
                            <i class="fa fa-trash-o"></i>
                        </button>
                    </div>';
               })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('backend.layouts.feedback.index');
    }
    public function show(int $id): View | RedirectResponse
    {
        try {
            $data = Feedback::with('user')->findOrFail($id);
            return view('backend.layouts.feedback.show', compact('data'));
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Feedback not found!');
        }
    }

    /**
     * Change feedback status (Pending -> Resolved)
     */
    public function status(int $id): JsonResponse
    {
        try {
            $data = Feedback::findOrFail($id);
            $data->status = ($data->status == 'pending') ? 'resolved' : 'pending';
            $data->save();

            return response()->json([
                'success' => true,
                'message' => 'Status changed to ' . ucfirst($data->status) . ' successfully.',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong!'], 500);
        }
    }

    /**
     * Remove the specified feedback.
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            Feedback::findOrFail($id)->delete();
            return redirect()->back()->with('t-success', 'Feedback deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Something went wrong!');
        }
    }
}

<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{

    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $data = User::where('role', '!=', 'admin')->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('status', function ($data) {
                    $checked = !$data->is_suspended ? "checked" : "";
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
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                                <a href="' . route('admin.user.show', $data->id) . '" class="text-white btn btn-info" title="View">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <button type="button" onclick="deleteUser(' . $data->id . ')" class="text-white btn btn-danger" title="Delete">
                                    <i class="fa fa-trash-o"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('backend.layouts.user.index');
    }

    public function status(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            if ($user->role === 'admin') {
                return response()->json(['success' => false, 'message' => 'Cannot modify admin status.'], 403);
            }

            if ($user->is_suspended) {
                // Unsuspend করা হচ্ছে
                $user->update([
                    'is_suspended'      => false,
                    'suspension_reason' => null,
                    'suspended_at'      => null,
                    'suspended_by'      => null,
                ]);
                $message = "User unsuspended successfully.";
            } else {
                // Suspend করা হচ্ছে
                $user->update([
                    'is_suspended'      => true,
                    'suspension_reason' => 'Suspended by admin.',
                    'suspended_at'      => now(),
                    'suspended_by'      => auth()->id(),
                ]);

                // Session/Tokens clear করা
                $user->tokens()->delete();
                Cache::forget("refresh_tokens_{$user->id}");
                $message = "User suspended successfully.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $user,
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong!'], 500);
        }
    }
    public function show(int $id)
    {
        try {
            $data = User::where('role', '!=', 'admin')->findOrFail($id);
            return view('backend.layouts.user.show', compact('data'));
        } catch (Exception $e) {
            return redirect()->route('admin.user.index')->with('t-error', 'User not found!');
        }
    }

    public function destroy(int $id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->role === 'admin') {
                return redirect()->back()->with('t-error', 'Cannot delete an admin account.');
            }

            $user->delete();
            return redirect()->back()->with('t-success', 'User deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Something went wrong!');
        }
    }
}

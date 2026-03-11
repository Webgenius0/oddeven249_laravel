<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class VoucherController extends Controller
{
    /**
     * ইউজারের পাঠানো সকল ভাউচার লিস্ট দেখাবে (Pendingগুলো আগে আসবে)
     */
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            // ইউজারের নাম এবং ক্যাটাগরি সহ ডাটা নিয়ে আসা
            $data = Voucher::with(['user', 'category'])->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user', function ($data) {
                    return $data->user ? $data->user->name : 'N/A';
                })
                ->addColumn('category', function ($data) {
                    return $data->category ? $data->category->name : 'General';
                })
                ->addColumn('discount_info', function ($data) {
                    $symbol = $data->discount_type == 'percentage' ? '%' : '$';
                    return $data->discount . $symbol;
                })
                ->addColumn('validity', function ($data) {
                    return $data->start_date->format('d M') . ' - ' . $data->end_date->format('d M, Y');
                })
                ->addColumn('status', function ($data) {
                    // যদি status false থাকে তবে ইন-একটিভ দেখাবে (অ্যাডমিন ক্লিক করলে একটিভ হবে)
                    $checked = $data->status ? "checked" : "";
                    return '
                        <div class="form-check form-switch d-flex justify-content-center">
                            <input onclick="showStatusChangeAlert(' . $data->id . ')"
                                   type="checkbox"
                                   class="form-check-input status-toggle"
                                   id="switch' . $data->id . '"
                                   name="status" ' . $checked . '>
                        </div>';
                })
                ->addColumn('action', function ($data) {
                    return '
                        <button type="button" onclick="deleteVoucher(' . $data->id . ')" class="text-white btn btn-danger btn-sm" title="Delete">
                            <i class="fa fa-trash"></i>
                        </button>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('backend.layouts.voucher.index');
    }

    /**
     * ভাউচার অ্যাপ্রুভ বা রিজেক্ট করার জন্য স্ট্যাটাস টগল
     */
    public function status(int $id): JsonResponse
    {
        try {
            $data = Voucher::findOrFail($id);
            $data->status = !$data->status;
            $data->save();

            $statusText = $data->status ? 'Approved & Activated' : 'Deactivated';

            return response()->json([
                'success' => true,
                'message' => 'Voucher ' . $statusText . ' Successfully.',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.'
            ], 500);
        }
    }

    /**
     * কোনো ভুল বা ফালতু ভাউচার হলে অ্যাডমিন ডিলিট করে দিতে পারবে
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            Voucher::findOrFail($id)->delete();
            return redirect()->back()->with('t-success', 'Voucher removed successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Something went wrong!');
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    use ApiResponse;

    public function getAllVouchers(Request $request)
    {
        $query = Voucher::where('status', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $vouchers = $query->with('category:id,name')
                          ->orderBy('created_at', 'desc')
                          ->paginate(15);

        return $this->success($vouchers, 'All active vouchers retrieved successfully', 200);
    }
    public function index()
    {
        $vouchers = Voucher::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($vouchers, 'Vouchers retrieved successfully', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promo_code'    => 'required|string|unique:vouchers,promo_code',
            'discount'      => 'required|numeric|min:0',
            'discount_type' => 'required|in:percentage,fixed',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'category_id'   => 'nullable|exists:categories,id',
            'description'   => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        $voucher = Voucher::create([
            'user_id'       => Auth::id(),
            'category_id'   => $request->category_id,
            'promo_code'    => strtoupper($request->promo_code),
            'description'   => $request->description,
            'discount'      => $request->discount,
            'discount_type' => $request->discount_type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'status'        => false,
        ]);

        return $this->success($voucher, 'Voucher created successfully and pending activation.', 201);
    }
    public function show($id)
    {
        $voucher = Voucher::where('user_id', Auth::id())->find($id);

        if (!$voucher) {
            return $this->error(null, 'Voucher not found', 404);
        }

        return $this->success($voucher, 'Voucher details found');
    }
    public function destroy($id)
    {
        $voucher = Voucher::where('user_id', Auth::id())->find($id);

        if (!$voucher) {
            return $this->error(null, 'Voucher not found', 404);
        }
        $voucher->delete();

        return $this->success(null, 'Voucher deleted successfully');
    }

}

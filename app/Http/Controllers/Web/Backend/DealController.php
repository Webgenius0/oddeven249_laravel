<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\DealDispute;
use App\Services\DisputeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class DealController extends Controller
{
    protected $disputeService;

    public function __construct(DisputeService $disputeService)
    {
        $this->disputeService = $disputeService;
    }

    /**
     * Display a listing of all deals for Admin.
     */
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $data = Deal::with(['buyer:id,name', 'seller:id,name'])->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('buyer', function ($row) {
                    return $row->buyer->name ?? 'N/A';
                })
                ->addColumn('seller', function ($row) {
                    return $row->seller->name ?? 'N/A';
                })
                ->addColumn('status', function ($row) {
                    $class = match($row->status) {
                        'active'    => 'badge bg-success',
                        'disputed'  => 'badge bg-danger',
                        'completed' => 'badge bg-info',
                        default     => 'badge bg-secondary',
                    };
                    return '<span class="'.$class.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                                <a href="' . route('admin.deal.show', $data->id) . '" class="text-white btn btn-info" title="View Details">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('backend.layouts.deal.index');
    }

    /**
     * Display specific deal details.
     */
    public function show(int $id): View
    {
        $deal = Deal::with(['buyer', 'seller', 'dispute'])->findOrFail($id);
        return view('backend.layouts.deal.show', compact('deal'));
    }

    /**
     * Handle Dispute Resolution from Web Backend (Admin).
     */
    public function resolveDispute(Request $request)
    {
        $request->validate([
            'dispute_id' => 'required|exists:deal_disputes,id',
            'resolution' => 'required|in:refund_buyer,release_seller',
            'admin_note' => 'nullable|string',
        ]);

        try {
            $admin = Auth::user();
            $this->disputeService->resolveDispute($admin, $request->all());
            return redirect()->back()->with('t-success', 'Dispute resolved successfully as ' . str_replace('_', ' ', $request->resolution));

        } catch (Exception $e) {
            return redirect()->back()->with('t-error', $e->getMessage());
        }
    }

    /**
     * Mark a dispute as Under Review.
     */
    public function markUnderReview(int $disputeId): JsonResponse
    {
        try {
            $admin = Auth::user();
            $dispute = $this->disputeService->markUnderReview($admin, $disputeId);

            return response()->json([
                'success' => true,
                'message' => 'Dispute is now under review.',
                'data'    => $dispute
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}

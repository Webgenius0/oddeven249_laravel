<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class SupportController extends Controller
{
    /**
     * Display a listing of Support Tickets using Yajra DataTables.
     */
    public function index(Request $request): View | JsonResponse
    {
        if ($request->ajax()) {
            $data = Ticket::with('user:id,name')->latest();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('user_name', function ($data) {
                    return $data->user->name ?? 'N/A';
                })
                ->addColumn('status', function ($data) {
                    $statusClass = $data->status == 'open' ? 'badge-light-danger' : ($data->status == 'pending' ? 'badge-light-warning' : 'badge-light-success');
                    return '<span class="badge ' . $statusClass . '">' . ucfirst($data->status) . '</span>';
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group">
                        <a href="' . route('admin.support.show', $data->id) . '" class="text-white btn btn-info" title="View & Reply">
                            <i class="fa fa-commenting-o"></i>
                        </a>
                        <button type="button" onclick="deleteTicket(' . $data->id . ')" class="text-white btn btn-danger" title="Delete">
                            <i class="fa fa-trash-o"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('backend.layouts.support.index');
    }

    /**
     * Show ticket conversation details.
     */
    public function show(int $id): View | RedirectResponse
    {
        try {
            $ticket = Ticket::with(['user', 'messages.sender'])->findOrFail($id);
            return view('backend.layouts.support.show', compact('ticket'));
        } catch (Exception $e) {
            return redirect()->back()->with('t-error', 'Ticket not found!');
        }
    }

    /**
     * Admin reply to a ticket.
     */
    public function adminReply(Request $request, int $id): RedirectResponse
    {
        $request->validate(['message' => 'required']);

        try {
            SupportMessage::create([
                'ticket_id' => $id,
                'sender_id' => auth()->id(),
                'message'   => $request->message,
            ]);
            Ticket::where('id', $id)->update(['status' => 'pending']);

            return back()->with('t-success', 'Reply sent successfully!');
        } catch (Exception $e) {
            return back()->with('t-error', 'Something went wrong!');
        }
    }


    public function destroy(int $id): JsonResponse
    {
        try {
            Ticket::findOrFail($id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Ticket deleted successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Something went wrong!'], 500);
        }
    }
}

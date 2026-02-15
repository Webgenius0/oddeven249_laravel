<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SupportController extends Controller
{
    use ApiResponse;
    public function storeTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2500',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'subject' => $request->subject,
                'status'  => 'open',
            ]);
            SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender_id' => auth()->id(),
                'message'   => $request->message,
            ]);

            return $this->success($ticket->load('messages'), 'Support request submitted successfully.', 201);
        } catch (Exception $e) {
            return $this->error(null, 'Something went wrong while creating ticket.', 500);
        }
    }

    public function getMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'ticket_id is required and must be an integer.', 422);
        }

        try {
            $ticketId = $request->ticket_id;
            Ticket::where('user_id', auth()->id())->findOrFail($ticketId);

            $messages = SupportMessage::with('sender:id,name,avatar')
                ->where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get();

            return $this->success($messages, 'Messages retrieved successfully.', 200);
        } catch (ModelNotFoundException $e) {
            return $this->error(null, 'Ticket not found or unauthorized access.', 404);
        } catch (Exception $e) {
            return $this->error(null, 'Internal Server Error.', 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|integer|exists:tickets,id',
            'message'   => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation Error', 422);
        }

        try {
            $ticketId = $request->ticket_id;
            Ticket::where('user_id', auth()->id())->findOrFail($ticketId);

            $message = SupportMessage::create([
                'ticket_id' => $ticketId,
                'sender_id' => auth()->id(),
                'message'   => $request->message,
            ]);

            return $this->success($message, 'Message sent successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->error(null, 'Unauthorized or Ticket not found.', 403);
        } catch (Exception $e) {
            return $this->error(null, 'Could not send message.', 500);
        }
    }
}

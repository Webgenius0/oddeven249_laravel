@extends('backend.app')
@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5>Ticket: {{ $ticket->subject }}</h5>
                    <span>User: {{ $ticket->user->name }}</span>
                </div>
                <div class="card-body chat-body" style="height: 450px; overflow-y: auto; background: #f9f9f9;">
                    @foreach ($ticket->messages as $msg)
                        <div
                            class="mb-3 d-flex {{ $msg->sender_id == auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                            <div class="p-3 shadow-sm"
                                style="border-radius: 15px; max-width: 70%; 
                            background: {{ $msg->sender_id == auth()->id() ? '#007bff; color: #fff;' : '#fff5e1; color: #333;' }}">
                                <strong>{{ $msg->sender->name }}:</strong>
                                <p class="mb-0">{{ $msg->message }}</p>
                                <small class="opacity-75">{{ $msg->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.support.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <input type="text" name="message" class="form-control" placeholder="Type your reply..."
                                required>
                            <button class="btn btn-primary" type="submit">Send Reply</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('backend.app')

@section('title', 'Deal Details')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4>Deal: {{ $deal->campaign_name }}</h4>
                            <span class="badge {{ $deal->status == 'disputed' ? 'bg-danger' : 'bg-primary' }} p-2">
                                {{ strtoupper($deal->status) }}
                            </span>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <h6>Buyer Details</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $deal->buyer->name }}</p>
                                <p><strong>Email:</strong> {{ $deal->buyer->email }}</p>
                            </div>
                            <div class="col-6">
                                <h6>Seller Details</h6>
                                <p class="mb-1"><strong>Name:</strong> {{ $deal->seller->name }}</p>
                                <p><strong>Email:</strong> {{ $deal->seller->email }}</p>
                            </div>
                        </div>

                        <hr>

                        <h6>Campaign Description</h6>
                        <p>{{ $deal->description ?? 'No description provided.' }}</p>

                        <div class="row mt-4">
                            <div class="col-4">
                                <strong>Amount:</strong>
                                <h5 class="text-success">${{ number_format($deal->amount, 2) }}</h5>
                            </div>
                            <div class="col-4">
                                <strong>Duration:</strong>
                                <p>{{ $deal->duration }}</p>
                            </div>
                            <div class="col-4">
                                <strong>Valid Until:</strong>
                                <p>{{ \Carbon\Carbon::parse($deal->valid_until)->format('d M, Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    @if ($deal->dispute)
                        <div class="card border-danger p-4">
                            <h5 class="text-danger mb-3"><i class="fa fa-gavel"></i> Dispute Raised</h5>
                            <p><strong>Reason:</strong> {{ $deal->dispute->reason }}</p>

                            @if ($deal->dispute->attachment)
                                <a href="{{ asset($deal->dispute->attachment) }}" target="_blank"
                                    class="btn btn-sm btn-outline-secondary mb-3">
                                    <i class="fa fa-paperclip"></i> View Attachment
                                </a>
                            @endif

                            <hr>

                            @if ($deal->dispute->status !== 'resolved')
                                <div class="d-grid gap-2">
                                    <button onclick="resolveDispute('refund_buyer')" class="btn btn-warning">
                                        <i class="fa fa-undo"></i> Refund to Buyer
                                    </button>
                                    <button onclick="resolveDispute('release_seller')" class="btn btn-success">
                                        <i class="fa fa-check"></i> Release to Seller
                                    </button>
                                    @if ($deal->dispute->status == 'open')
                                        <button onclick="markReview()" class="btn btn-info text-white">
                                            <i class="fa fa-eye"></i> Mark Under Review
                                        </button>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-success">
                                    <h6>Resolved</h6>
                                    <p class="small mb-0">Resolution:
                                        {{ str_replace('_', ' ', $deal->dispute->resolution) }}</p>
                                    <p class="small">Note: {{ $deal->dispute->admin_note }}</p>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="card p-4 text-center">
                            <p class="text-muted mb-0">No active dispute for this deal.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <form id="resolve-dispute-form" action="{{ route('admin.deal.dispute.resolve') }}" method="POST"
            style="display: none;">
            @csrf
            <input type="hidden" name="dispute_id" value="{{ $deal->dispute->id ?? '' }}">
            <input type="hidden" name="resolution" id="resolution_input">
            <textarea name="admin_note" id="admin_note_input"></textarea>
        </form>
    </div>
@endsection

@push('script')
    <script>
        function resolveDispute(type) {
            let title = type === 'refund_buyer' ? 'Refund Buyer?' : 'Release to Seller?';
            let text = type === 'refund_buyer' ?
                "The full amount will be returned to the buyer's wallet." :
                "The amount (minus commission) will be settled to the seller's wallet.";

            Swal.fire({
                title: title,
                text: text,
                input: 'textarea',
                inputPlaceholder: 'Add an admin note about this decision (optional)...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Proceed!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('resolution_input').value = type;
                    document.getElementById('admin_note_input').value = result.value;
                    document.getElementById('resolve-dispute-form').submit();
                }
            });
        }

        function markReview() {
            $.post("{{ route('admin.deal.dispute.review', $deal->dispute->id ?? 0) }}", {
                _token: "{{ csrf_token() }}"
            }, function(resp) {
                if (resp.success) {
                    toastr.success(resp.message);
                    location.reload();
                }
            });
        }
    </script>
@endpush

@extends('backend.app')

@section('title', 'All Deals')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="p-5 card">
                        <div class="card-style mb-30">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <h4>Deal List</h4>
                            </div>
                            <div class="table-wrapper table-responsive">
                                <table id="data-table" class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Campaign Name</th>
                                            <th>Buyer</th>
                                            <th>Seller</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Dynamic Data --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(document).ready(function() {
            // AJAX CSRF Setup
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });

            // DataTable Initialization
            if (!$.fn.DataTable.isDataTable('#data-table')) {
                $('#data-table').DataTable({
                    order: [],
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    processing: true,
                    responsive: true,
                    serverSide: true,
                    language: {
                        processing: `<div class="text-center">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                          </div>
                            </div>`
                    },
                    pagingType: "full_numbers",
                    dom: "<'row justify-content-between table-topbar'<'col-md-2 col-sm-4 px-0'l><'col-md-2 col-sm-4 px-0'f>>tipr",
                    ajax: {
                        url: "{{ route('admin.deal.index') }}",
                        type: "get",
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'campaign_name',
                            name: 'campaign_name',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'buyer',
                            name: 'buyer',
                            orderable: false,
                            searchable: true
                        },
                        {
                            data: 'seller',
                            name: 'seller',
                            orderable: false,
                            searchable: true
                        },
                        {
                            data: 'amount',
                            name: 'amount',
                            orderable: true,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'status',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                    ],
                });
            }
        });

        // View Deal Details (Redirect to Show page)
        function viewDeal(id) {
            let url = "{{ route('admin.deal.show', ':id') }}";
            window.location.href = url.replace(':id', id);
        }
    </script>
@endpush

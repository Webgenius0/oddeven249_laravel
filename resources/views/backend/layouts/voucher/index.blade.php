@extends('backend.app')

@section('title', 'Voucher Requests')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="p-5 card">
                        <div class="card-style mb-30">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <h4>Voucher Approval List</h4>
                                {{-- এখানে 'Add New' বাটন নেই কারণ অ্যাডমিন শুধু রিভিউ করবে --}}
                            </div>
                            <div class="table-wrapper table-responsive">
                                <table id="data-table" class="table text-center">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User</th>
                                            <th>Promo Code</th>
                                            <th>Category</th>
                                            <th>Discount</th>
                                            <th>Validity</th>
                                            <th>Status (Approve)</th>
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
                    {{-- ডিলিট করার জন্য হিডেন ফর্ম --}}
                    <form id="delete-form" action="" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
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

            // DataTables Initialization
            if (!$.fn.DataTable.isDataTable('#data-table')) {
                $('#data-table').DataTable({
                    order: [],
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
                    ajax: {
                        url: "{{ route('admin.voucher.index') }}",
                        type: "get",
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'user',
                            name: 'user'
                        },
                        {
                            data: 'promo_code',
                            name: 'promo_code'
                        },
                        {
                            data: 'category',
                            name: 'category'
                        },
                        {
                            data: 'discount_info',
                            name: 'discount_info'
                        },
                        {
                            data: 'validity',
                            name: 'validity'
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

        // Approve/Status Change Alert
        function showStatusChangeAlert(id) {
            Swal.fire({
                title: 'Change Status?',
                text: 'This will activate/deactivate the voucher for the user.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, do it!',
            }).then((result) => {
                if (result.isConfirmed) {
                    statusChange(id);
                } else {
                    $('#data-table').DataTable().ajax.reload(null, false);
                }
            });
        }

        // Ajax for Status Change
        function statusChange(id) {
            let url = '{{ route('admin.voucher.status', ':id') }}';
            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    $('#data-table').DataTable().ajax.reload(null, false);
                    if (resp.success) {
                        toastr.success(resp.message);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function() {
                    toastr.error("Something went wrong!");
                    $('#data-table').DataTable().ajax.reload(null, false);
                }
            });
        }

        // Delete Voucher
        function deleteVoucher(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "The user will lose this voucher request!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('admin.voucher.destroy', ':id') }}";
                    url = url.replace(':id', id);
                    let form = document.getElementById('delete-form');
                    form.action = url;
                    form.submit();
                }
            });
        }
    </script>
@endpush

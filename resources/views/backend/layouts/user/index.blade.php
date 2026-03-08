@extends('backend.app')

@section('title', 'Users')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="p-5 card">
                        <div class="card-style mb-30">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <h4>User List</h4>
                                {{-- ইউজার সাধারণত সিস্টেম থেকে রেজিস্টার করে, তাই এখানে অ্যাড বাটন রাখা হয়নি। প্রয়োজন হলে যোগ করতে পারেন --}}
                            </div>
                            <div class="table-wrapper table-responsive">
                                <table id="data-table" class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
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
            // AJAX Setup
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
                        url: "{{ route('admin.user.index') }}", // আপনার রাউট নাম অনুযায়ী পরিবর্তন করুন
                        type: "get",
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'email',
                            name: 'email',
                            orderable: true,
                            searchable: true
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
        function showStatusChangeAlert(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to change the status (Active/Suspend) for this user?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, change it!',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    statusChange(id);
                } else {
                    $('#data-table').DataTable().ajax.reload(null, false);
                }
            });
        }
        function statusChange(id) {
            let url = '{{ route('admin.user.status', ':id') }}';
            $.ajax({
                type: "GET",
                url: url.replace(':id', id),
                success: function(resp) {
                    $('#data-table').DataTable().ajax.reload(null, false);
                    if (resp.success === true) {
                        toastr.success(resp.message);
                    } else {
                        toastr.error(resp.message);
                    }
                },
                error: function() {
                    toastr.error("Something went wrong!");
                }
            });
        }
        function deleteUser(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "User's data will be permanently removed!",
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('admin.user.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    let form = document.getElementById('delete-form');
                    form.action = url;
                    form.submit();
                }
            });
        }
    </script>
@endpush

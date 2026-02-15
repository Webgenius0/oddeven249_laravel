@extends('backend.app')

@section('title', 'Feedback Details')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12 col-xl-6">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h5>User Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Name</th>
                                    <td>{{ $data->user->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td>{{ $data->user->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Role</th>
                                    <td><span class="badge badge-primary">{{ ucfirst($data->user->role ?? 'User') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-sm-12 col-xl-6">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h5>Feedback Details</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Type</th>
                                    <td><span class="text-info font-weight-bold">{{ ucfirst($data->type) }}</span></td>
                                </tr>
                                <tr>
                                    <th>Current Status</th>
                                    <td><span
                                            class="badge {{ $data->status == 'resolved' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($data->status) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Submitted At</th>
                                    <td>{{ $data->created_at->format('d M, Y (h:i A)') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h5>Feedback Message</h5>
                        </div>
                        <div class="card-body">
                            <div style="color: black" class="p-4 bg-light rounded">
                                <p style="white-space: pre-wrap;">{{ $data->message }}</p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.feedback.index') }}" class="btn btn-secondary">Back to List</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

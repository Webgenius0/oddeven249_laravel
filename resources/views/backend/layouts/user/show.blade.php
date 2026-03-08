@extends('backend.app')

@section('title', 'User Details')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>User Information</h5>
                                <a href="{{ route('admin.user.index') }}" class="btn btn-primary">Back to List</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center border-end">
                                    <div class="mb-3">
                                        <img src="{{ $data->avatar ? asset($data->avatar) : asset('backend/assets/images/dashboard/profile.png') }}"
                                            alt="User Avatar" class="rounded-circle img-fluid"
                                            style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #f0f0f0;">
                                    </div>
                                    <h4>{{ $data->name }}</h4>
                                    <p class="text-muted">{{ ucfirst($data->role) }}</p>
                                    <span class="badge {{ $data->is_suspended ? 'bg-danger' : 'bg-success' }}">
                                        {{ $data->is_suspended ? 'Suspended' : 'Active' }}
                                    </span>
                                </div>

                                <div class="col-md-8">
                                    <table class="table table-borderless mt-3">
                                        <tbody>
                                            <tr>
                                                <th style="width: 30%;">Full Name :</th>
                                                <td>{{ $data->name }}</td>
                                            </tr>
                                            <tr>
                                                <th>Email Address :</th>
                                                <td>{{ $data->email }}</td>
                                            </tr>
                                            <tr>
                                                <th>Joined Date :</th>
                                                <td>{{ $data->created_at->format('d M, Y (h:i A)') }}</td>
                                            </tr>

                                            @if ($data->is_suspended)
                                                <tr class="table-danger">
                                                    <th>Suspension Reason :</th>
                                                    <td>{{ $data->suspension_reason ?? 'No reason provided.' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Suspended At :</th>
                                                    <td>{{ \Carbon\Carbon::parse($data->suspended_at)->format('d M, Y') }}
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>

                                    <div class="mt-4">
                                        <h6>Account Statistics</h6>
                                        <hr>
                                        {{-- <div class="row text-center">
                                            <div class="col-4">
                                                <p class="mb-1 text-muted">Total Posts</p>
                                                <h5>{{ $data->posts_count ?? 0 }}</h5>
                                            </div>
                                            <div class="col-4">
                                                <p class="mb-1 text-muted">Comments</p>
                                                <h5>{{ $data->comments_count ?? 0 }}</h5>
                                            </div>
                                            <div class="col-4">
                                                <p class="mb-1 text-muted">Balance</p>
                                                <h5>${{ number_format($data->balance ?? 0, 2) }}</h5>
                                            </div>
                                        </div> --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

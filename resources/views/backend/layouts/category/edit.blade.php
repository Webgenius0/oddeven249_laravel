@extends('backend.app')

@section('title', 'Edit Category')

@section('content')
    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card p-4">
                        <div class="card-header pb-0">
                            <h5>Edit Category</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.category.update', $data->id) }}" method="POST">
                                @csrf
                                @method('POST') {{-- Apnar Controller e update method jodi POST hoy tobe POST rakhen --}}

                                <div class="mb-3">
                                    <label class="form-label" for="name">Category Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name', $data->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Update Category</button>
                                    <a href="{{ route('admin.category.index') }}" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

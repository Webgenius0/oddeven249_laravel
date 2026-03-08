@extends('backend.app')
@section('title', 'System settings')

@section('content')

    <div class="page-body">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card-style mb-4">
                        <div class="card card-body">
                            <form method="POST" action="{{ route('admin.system.update') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="title">Title:</label>
                                            <input type="text" placeholder="Enter Title" id="title"
                                                class="form-control @error('title') is-invalid @enderror" name="title"
                                                value="{{ $setting->title ?? '' }}" />
                                            @error('title')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="email">Email:</label>
                                            <input type="email" placeholder="Enter Email" id="email"
                                                class="form-control @error('email') is-invalid @enderror" name="email"
                                                value="{{ $setting->email ?? '' }}" />
                                            @error('email')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="system_name">System Name:</label>
                                            <input type="text" placeholder="System Name" id="system_name"
                                                class="form-control @error('system_name') is-invalid @enderror"
                                                name="system_name" value="{{ $setting->system_name ?? '' }}" />
                                            @error('system_name')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="copyright_text">Copy Rights Text:</label>
                                            <input type="text" placeholder="Copy Rights Text" id="copyright_text"
                                                class="form-control @error('copyright_text') is-invalid @enderror"
                                                name="copyright_text" value="{{ $setting->copyright_text ?? '' }}" />
                                            @error('copyright_text')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="platform_commission">Platform Commission (%):</label>
                                            <input type="number" step="0.01" placeholder="e.g. 10.00"
                                                id="platform_commission"
                                                class="form-control @error('platform_commission') is-invalid @enderror"
                                                name="platform_commission"
                                                value="{{ $setting->platform_commission ?? '0.00' }}" />
                                            <small class="text-muted">Percentage to be deducted from deals.</small>
                                            @error('platform_commission')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="tax_rate">Tax Rate (%):</label>
                                            <input type="number" step="0.01" placeholder="e.g. 5.00" id="tax_rate"
                                                class="form-control @error('tax_rate') is-invalid @enderror" name="tax_rate"
                                                value="{{ $setting->tax_rate ?? '0.00' }}" />
                                            <small class="text-muted">Government tax percentage on total deal
                                                amount.</small>
                                            @error('tax_rate')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="logo">Logo: <small class="text-muted">(Recommended:
                                                    115×36px)</small></label>
                                            <input type="file" class="dropify @error('logo') is-invalid @enderror"
                                                name="logo" id="logo"
                                                data-default-file="@isset($setting){{ asset($setting->logo) }}@endisset" />
                                        </div>
                                        @error('logo')
                                            <span class="text-danger" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>


                                    <div class="col-md-6 mt-4">
                                        <div class="input-style-1">
                                            <label for="favicon">Favicon:</label>
                                            <input type="file" class="dropify @error('favicon') is-invalid @enderror"
                                                name="favicon" id="favicon"
                                                data-default-file="@isset($setting){{ asset($setting->favicon) }}@endisset" />
                                        </div>

                                        @error('favicon')
                                            <span class="text-danger" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 mt-4">
                                        <div class="input-style-1">
                                            <label for="description">About System:</label>
                                            <textarea placeholder="Type here..." id="description" name="description"
                                                class="form-control @error('description') is-invalid @enderror">
                                            {{ $setting->description ?? '' }}
                                        </textarea>
                                            @error('description')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                    <a href="{{ route('admin.dashboard') }}" class="btn btn-danger me-2">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        ClassicEditor
            .create(document.querySelector('#description'))
            .catch(error => {
                console.error(error);
            });
    </script>
@endpush

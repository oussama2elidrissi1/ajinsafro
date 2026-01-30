@extends('layouts.master-ajinsafro')
@section('title')
    My Profile
@endsection
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">My Profile</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">My Profile</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-12 col-xl-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="py-3">
                        <img src="{{ $user->avatar_url }}" alt="" class="avatar-lg mx-auto img-thumbnail rounded-circle">
                    </div>
                    <h5 class="font-size-16 mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-3">{{ $user->email }}</p>

                    <form action="{{ route('admin.profile.avatar') }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="mb-2">
                            <input type="file" name="avatar" id="avatar" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm waves-effect waves-light">Upload Avatar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Profile Information</h4>

                    <form action="{{ route('admin.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3 row">
                            <label for="name" class="col-md-3 col-form-label">Name <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="email" class="col-md-3 col-form-label">Email <span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input class="form-control" type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="phone" class="col-md-3 col-form-label">Phone</label>
                            <div class="col-md-9">
                                <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" placeholder="Optional">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="address" class="col-md-3 col-form-label">Address</label>
                            <div class="col-md-9">
                                <textarea class="form-control" name="address" id="address" rows="2" placeholder="Optional">{{ old('address', $user->address) }}</textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="card-title mb-3">Change Password (optional)</h5>

                        <div class="mb-3 row">
                            <label for="current_password" class="col-md-3 col-form-label">Current Password</label>
                            <div class="col-md-9">
                                <input class="form-control" type="password" name="current_password" id="current_password" placeholder="Leave blank to keep current">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="new_password" class="col-md-3 col-form-label">New Password</label>
                            <div class="col-md-9">
                                <input class="form-control" type="password" name="new_password" id="new_password" placeholder="Leave blank to keep current">
                            </div>
                        </div>

                        <div class="mb-3 row">
                            <label for="new_password_confirmation" class="col-md-3 col-form-label">Confirm New Password</label>
                            <div class="col-md-9">
                                <input class="form-control" type="password" name="new_password_confirmation" id="new_password_confirmation" placeholder="Confirm new password">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-9 offset-md-3">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">Update Profile</button>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary waves-effect waves-light ms-2">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

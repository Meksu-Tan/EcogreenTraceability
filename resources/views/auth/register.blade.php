@extends('layouts.app_admin')
@section('title-head','PTEO EUDR-TS')
@section('title','Admin Dashboard - User Management')
@section('content')

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-2" style="padding-bottom:15px">
                    <button type="button" id="back" class="btn btn-secondary"><i class="fas fa-gem" aria-hidden="true"></i> &nbsp Back </button>
                </div>
            </div>
            <div class="card card-primary">
                <div class="card-header">{{ __('Register') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Name -->
                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- TYpe -->
                        <div class="row mb-3">
                            <label for="type" class="col-md-4 col-form-label text-md-end">{{ __('Authorization') }}</label>

                            <div class="col-md-6">
                                <select name="type" id="type" style="width: 100%;" class="form-control" required>
                                    <option value=""></option>
                                    <option value="admin"> Admin </option>
                                    <option value="manager"> Manager </option>
                                    <option value="superintendent"> Superintendent </option>
                                    <option value="senior-supervisor"> Senior-supervisor </option>
                                    <option value="supervisor"> Supervisor </option>
                                    <option value="senior-staff"> Senior-staff </option>
                                    <option value="staff"> Staff </option>
                                    <option value="viewer"> Viewer </option>
                                </select>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')

<!-- SCRIPT -->
<script>
    const $btn_back = '#back';

    $(document).ready(function() {
        /* INITIALIZE */
            $('.modal').css('overflow-y', 'auto');

        /* LISTENER ON MODAL FORM SUBMIT */

        /* LISTENER ON SINGLE-CLICK */
            $(document).on('click', $btn_back, function(){
                console.log(1);
                window.location.href = "{{ route('admin.index') }}";
            });

        /* LISTENER ON INPUT CHANGE */

    });

</script>
@endpush

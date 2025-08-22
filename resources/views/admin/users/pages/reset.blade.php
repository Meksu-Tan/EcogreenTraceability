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
            <div class="card">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    <form id="form-resetPassword" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

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

                        <div class="row mb-3">
                            <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Reset Password') }}
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
    var post_url    = "{{ route('admin.store') }}";

    const $btn_back = '#back';
    const $form_resetPassword = '#form-resetPassword';

    $(document).ready(function() {
        /* INITIALIZE */
            $('.modal').css('overflow-y', 'auto');

        /* LISTENER ON MODAL FORM SUBMIT */
            $($form_resetPassword).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);

                    Swal.fire({
                        title: 'Confirm Action',
                        text: 'Reset PASSWORD?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, reset it',
                        cancelButtonText: 'No, cancel',
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                url: post_url,
                                method: "POST",
                                data: formData,
                                contentType: false,
                                cache: false,
                                processData: false,
                                dataType: "JSON",
                                success: function(data) {
                                    if (data.status == 1) {
                                        Swal.fire(data.message, "", "success");
                                        window.location.href = "{{ route('admin.index') }}";
                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });
                });

        /* LISTENER ON SINGLE-CLICK */
            $(document).on('click', $btn_back, function(){
                window.location.href = "{{ route('admin.index') }}";
            });

        /* LISTENER ON INPUT CHANGE */

    });

</script>
@endpush

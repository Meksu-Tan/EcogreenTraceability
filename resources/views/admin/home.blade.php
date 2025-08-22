@extends('layouts.app_admin')
@section('title-head','PTEO EUDR-TS')
@section('title','Admin Dashboard')
@section('content')

<section class="section">
	<div class="section-body">
        <div class="row">
			<div class="col-md-12">
				<div class="card" style="margin-top:-15px; background-color: black;">
					<div class="card-body">
                        <div class="row">
                            <div class="col-md-12 text-center"> <!-- Modified to center the content -->
                                <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                    <span class="badge badge-dark d-block w-100" id="oee-summary-label-header"
                                          style="font-size:45px; white-space: normal;">PP&H OPERATIONAL MONITORING DASHBOARD</span>
                                </h4>
                            </div>
                        </div>
                        <div class="row justify-content-left">
                            <div class="col-md-3">
                                <select name="section" id="filter-section" style="width: 100%;" class="form-control" required>
                                    <option val=''>- All Section -</option>
                                </select>
                            </div>
                            <div class="col-md-3" style="text-align: center; padding-top:5px">
                                <button type="button" id="start-section-monitoring" class="btn btn-info" style="color:black">
                                    <i class="fa fa-bookmark" aria-hidden="true"></i> &nbsp Start Monitoring
                                </button>
                                <button type="button" id="stop-section-monitoring" class="btn btn-danger" style="color:white" hidden>
                                    <i class="fa fa-bookmark" aria-hidden="true"></i> &nbsp Stop Monitoring
                                </button>
                            </div>
                            <div class="col-md-2" style="text-align:center; padding-top:5px">
                                <h4 class="card-header-title">
                                    <span class="badge badge-dark" id="status-section-monitoring-time"> last update 09:00:00</span>
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top:-20px">
            <div class="col-md-3">
                <div class="card" style="background-color: black;">
                    <div class="card-body">
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-dark d-block w-100" style="font-size:22px; white-space: normal;">88 :: IBC GTCC</span>
                            </h4>
                        </div>
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-primary d-block w-100" style="white-space: normal;">RUNNING <span id="batch-no">(XX-YYY-ZZ)</span></span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background-color: black;">
                    <div class="card-body">
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-dark d-block w-100" style="font-size:22px; white-space: normal;">62 :: IBC ECOCEROL</span>
                            </h4>
                        </div>
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-primary d-block w-100" style="white-space: normal;">RUNNING <span id="batch-no; white-space: normal;">(XX-YYY-ZZ)</span></span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background-color: black;">
                    <div class="card-body">
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-dark d-block w-100" style="font-size:22px; white-space: normal;">71 :: IBC ECORIC</span>
                            </h4>
                        </div>
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-primary d-block w-100" style="white-space: normal;">RUNNING <span id="batch-no">(XX-YYY-ZZ)</span></span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background-color: black;">
                    <div class="card-body">
                    <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-dark d-block w-100" style="font-size:22px; white-space: normal;">58 :: DRUMMING GTCC</span>
                            </h4>
                        </div>
                        <div class="row">
                            <h4 class="card-header-title w-100"> <!-- Added font size here -->
                                <span class="badge badge-primary d-block w-100" style="white-space: normal;">RUNNING <span id="batch-no">(XX-YYY-ZZ)</span></span>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

@endsection
@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('dashboard.index') }}";
        var post_url    = "{{ route('dashboard.store') }}";
        var show_url    = "{{ route('dashboard.show', ':id') }}";

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');


        });

</script>
@endpush

@extends('layouts.app_user')
@section('title', $role)

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
                                          style="font-size:35px; white-space: normal;">TRACEABILITY SYSTEM MONITORING DASHBOARD</span>
                                </h4>
                            </div>
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

    /* VAR INDEX & PARAMETERIZATION */


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');



            /* EVENT LISTENER ON CHANGE */


        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */

    /* FUNCTION DYNAMICS */


    /* FUNCTION AUTO-REFRESH */
        function startAutoRefresh() {
            $intervalId = setInterval(function () {

                var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
                var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('-');
                var currentTime = time_format(new Date());

                $($status_oeeMonitoring_time).html('last update ' + currentTime);
                //ajax_updateRecord(currentDate, currentTime);

            }, 30000); // 120000 milliseconds = 2 minutes
        }
        function stopAutoRefresh() {
            clearInterval($intervalId); // Clear the interval using the stored interval ID
        }
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            seconds = format_two_digits(d.getSeconds());

            return hours + ":" + minutes + ":" + seconds;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

</script>
@endpush

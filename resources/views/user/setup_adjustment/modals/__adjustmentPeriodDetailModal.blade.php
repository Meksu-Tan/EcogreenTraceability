<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjPeriodDetail">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Stock Period Adjustment Detail</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">



                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('adjustment.index') }}";
        var post_url    = "{{ route('adjustment.store') }}";
        var show_url    = "{{ route('adjustment.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */



    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */


            /* LISTENER ON CLICK FUNCTION */


            /* LISTENER ON MODAL STACK */
                $($modal_adjustmentPeriodDetail).on('show.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 0.3);
                    }
                });
                $($modal_adjustmentPeriodDetail).on('hidden.bs.modal', function () {
                    if ( $($modal_adjustmentPeriodHeader).hasClass('show') ) {
                        $($modal_adjustmentPeriodHeader).css('opacity', 1);
                    }
                });

        });

    /* FUNCTION AJAX */


    /* FUNCTION INIT */


    /* FUNCTION AUTO-REFRESH */
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

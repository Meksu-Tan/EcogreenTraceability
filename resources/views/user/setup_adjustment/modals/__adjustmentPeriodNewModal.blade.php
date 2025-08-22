<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjPeriodNew">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>New Adjustment Period</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-adjPeriodNew" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-adjPeriodNew-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-adjPeriodNew-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-adjPeriodNew-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Period</label>
                                        <input type="date" name="period" id="form-adjPeriodNew-period" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Batch SAP</label>
                                        <input type="text" name="batch" id="form-adjPeriodNew-batch" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-adjPeriodNew">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
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
        const $form_adjPeriodNew                 = '#form-adjPeriodNew';

        const $txt_adjPeriodNew_flag             = '#form-adjPeriodNew-flag';
        const $txt_adjPeriodNew_mode             = '#form-adjPeriodNew-mode';
        const $txt_adjPeriodNew_id               = '#form-adjPeriodNew-id';

        const $txt_adjPeriodNew_period           = '#form-adjPeriodNew-period';
        const $txt_adjPeriodNew_batch            = '#form-adjPeriodNew-batch';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_adjPeriodNew).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_adjPeriodNew_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' New Period Adjustment ?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, ' + $mode + ' it',
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
                                        Swal.fire({
                                            position: 'top-end',
                                            icon: 'success',
                                            title: data.message,
                                            showConfirmButton: false,
                                            timer: 500
                                        });

                                        $($modal_adjustmentPeriodNew).modal('hide');
                                        initialize_adjustmentPeriodHeader();

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });

            /* LISTENER ON CLICK FUNCTION */


            /* LISTENER ON MODAL STACK */


        });

    /* FUNCTION AJAX */


    /* FUNCTION INIT */
        function initialize_adjustmentPeriodNew($flag=null, $mode=null, $id=null, $period=null, $batch=null){
            $($txt_adjPeriodNew_flag).val($flag);
            $($txt_adjPeriodNew_mode).val($mode);
            $($txt_adjPeriodNew_id).val($id);
            $($txt_adjPeriodNew_period).val($period);
            $($txt_adjPeriodNew_batch).val($batch);

            $($txt_adjPeriodNew_period).prop('readonly', false);

            if ($mode == 'UPDATE'){
                $($txt_adjPeriodNew_period).prop('readonly', true);
            }


        }

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

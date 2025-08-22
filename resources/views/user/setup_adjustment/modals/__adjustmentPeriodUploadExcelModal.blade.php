<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjPeriodUpload">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Upload Excel</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-adjPeriodUpload" method="post" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-adjPeriodUpload-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="mode" id="form-adjPeriodUpload-mode" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-adjPeriodUpload-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="from" id="form-adjPeriodUpload-from" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Get file</label>
                                        <input type="file" name="file" id="form-adjPeriodUpload-file" required>
                                    </div>
                                    <div id="message" style="color: red;"></div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-adjPeriodUpload">Save</button>
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
        const $form_adjPeriodUpload                 = '#form-adjPeriodUpload';

        const $txt_adjPeriodUpload_flag             = '#form-adjPeriodUpload-flag';
        const $txt_adjPeriodUpload_mode             = '#form-adjPeriodUpload-mode';
        const $txt_adjPeriodUpload_id               = '#form-adjPeriodUpload-id';
        const $txt_adjPeriodUpload_from             = '#form-adjPeriodUpload-from';
        const $txt_adjPeriodUpload_period           = '#form-adjPeriodUpload-file';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_adjPeriodUpload).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_adjPeriodUpload_mode).val();
                    var $from = $($txt_adjPeriodUpload_from).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' Excel file ?',
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

                                        $($modal_adjustmentPeriodUpload).modal('hide');
                                        if ($from == 'HEADER'){
                                            initialize_adjustmentPeriodHeader();
                                        } else if ($from == 'VIEW'){
                                            $($tbl_adjPeriodView).DataTable().ajax.reload();
                                        }

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                },
                                error: function(xhr) {
                                    var errors = xhr.responseJSON.errors;
                                    if (errors.file) {
                                        $('#message').html('<p>' + errors.file[0] + '</p>');
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
        function initialize_adjustmentPeriodUpload($flag=null, $mode=null, $id=null, $from=null){
            $($txt_adjPeriodUpload_flag).val($flag);
            $($txt_adjPeriodUpload_mode).val($mode);
            $($txt_adjPeriodUpload_id).val($id);
            $($txt_adjPeriodUpload_from).val($from);
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

<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-materialdoc-add">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-materialdoc-header">Add Material Document</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-materialdoc" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-materialdoc-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-materialdoc-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-materialdoc-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Document No</label>
                                        <textarea name="number" id="form-materialdoc-number" style="width: 100%; height: 200px;" required autocomplete="off"></textarea>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-materialdoc">Save</button>
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
        var index_url   = "{{ route('wipentry.index') }}";
        var post_url    = "{{ route('wipentry.store') }}";
        var show_url    = "{{ route('wipentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_materialdoc             = '#form-materialdoc';

        const $txt_materialdoc_flag         = '#form-materialdoc-flag';
        const $txt_materialdoc_mode         = '#form-materialdoc-mode';
        const $txt_materialdoc_id           = '#form-materialdoc-id';
        const $txt_materialdoc_header       = '#modal-materialdoc-header';

        const $txt_materialdoc_number       = '#form-materialdoc-number';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_materialdoc_modal();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_materialdoc).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_materialdoc_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' DOC NUMBER?',
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

                                        $($tbl_RmList).DataTable().ajax.reload();
                                        $($tbl_RmListTrf).DataTable().ajax.reload();

                                        $($modal_addMaterialDoc).modal('hide');

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });
                });
        });

    /* FUNCTION INITIALIZATION */
        function initialize_materialdoc_modal($flag=null, $mode=null, $header=null, $id=null, $number=null){
            $($txt_materialdoc_flag).val($flag);
            $($txt_materialdoc_mode).val($mode);
            $($txt_materialdoc_id).val($id);
            $($txt_materialdoc_header).html($header);
            $($txt_materialdoc_number).val($number);
        };

</script>
@endpush

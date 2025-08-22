<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-pckEntryBatch">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-pckEntryBatch-header">Entry of Batch Number</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-pckEntryBatch" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-pckEntryBatch-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-pckEntryBatch-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-pckEntryBatch-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">BATCH Number</label>
                                        <input type="text" name="batchNo" id="form-pckEntryBatch-batchNo" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="row" id="div-tank">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Destination Sloc (auto select)</label>
                                                <select name="warehouse" id="form-pckEntryBatch-warehouse" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Tank -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-pckEntryBatch">Save</button>
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
        var index_url   = "{{ route('packageentry.index') }}";
        var post_url    = "{{ route('packageentry.store') }}";
        var show_url    = "{{ route('packageentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_pckEntryBatch             = '#form-pckEntryBatch';

        const $txt_pckEntryBatch_flag         = '#form-pckEntryBatch-flag';
        const $txt_pckEntryBatch_mode         = '#form-pckEntryBatch-mode';
        const $txt_pckEntryBatch_id           = '#form-pckEntryBatch-id';

        const $txt_pckEntryBatch_batchNo      = '#form-pckEntryBatch-batchNo';
        const $txt_pckEntryBatch_warehouse    = '#form-pckEntryBatch-warehouse';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_pckEntryBatch).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_pckEntryBatch_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' BATCH NUMBER?',
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

                                        $($dt_pckEntry).DataTable().ajax.reload();
                                        $($modal_pckEntryBatch).modal('hide');

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });
                });
            /* LISTENER ON CHANGE FUNCTION */
                $(document).on('change', $txt_pckEntryBatch_batchNo, function(e){
                    e.preventDefault();

                    $batchNo = $($txt_pckEntryBatch_batchNo).val();
                    ajax_populateWarehouse($txt_pckEntryBatch_warehouse, $batchNo);
                });
        });

    /* FUNCTION INITIALIZATION */
        function initialize_pckEntryBatch($flag=null, $mode=null, $id=null, $batchNo=null){
            $($txt_pckEntryBatch_flag).val($flag);
            $($txt_pckEntryBatch_mode).val($mode);
            $($txt_pckEntryBatch_id).val($id);
            $($txt_pckEntryBatch_batchNo).val($batchNo);
            ajax_populateWarehouse($txt_pckEntryBatch_warehouse, $batchNo);
        };

</script>
@endpush

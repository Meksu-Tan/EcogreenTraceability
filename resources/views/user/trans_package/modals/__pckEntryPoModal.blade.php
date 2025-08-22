<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-pckEntryPo">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-pckEntryPo-header">Entry of PO / Shipment Lot No</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-pckEntryPo" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-pckEntryPo-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-pckEntryPo-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-pckEntryPo-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">PO</label>
                                        <input type="text" name="poNo" id="form-pckEntryPo-number" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-pckEntryPo">Save</button>
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
        const $form_pckEntryPo             = '#form-pckEntryPo';

        const $txt_pckEntryPo_flag         = '#form-pckEntryPo-flag';
        const $txt_pckEntryPo_mode         = '#form-pckEntryPo-mode';
        const $txt_pckEntryPo_id           = '#form-pckEntryPo-id';

        const $txt_pckEntryPo_poNo         = '#form-pckEntryPo-number';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_pckEntryPo).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_pckEntryPo_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' PO NUMBER?',
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
                                        $($modal_pckEntryPo).modal('hide');

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
        function initialize_pckEntryPo($flag=null, $mode=null, $id=null, $poNo=null){
            $($txt_pckEntryPo_flag).val($flag);
            $($txt_pckEntryPo_mode).val($mode);
            $($txt_pckEntryPo_id).val($id);
            $($txt_pckEntryPo_poNo).val($poNo);
        };

</script>
@endpush

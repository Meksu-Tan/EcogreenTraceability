<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-material">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-headerSectionMaterial">Setup Material</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-material" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-flagMaterial" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-idMaterial" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-modeMaterial" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Code</label>
                                        <input type="number" name="code" id="form-codeMaterial" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Code (Non-EUDR)</label>
                                        <input type="number" name="code_noneudr" id="form-codeMaterialNonEudr" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Code (Supplier)</label>
                                        <input type="text" name="code_matl_supplier" id="form-codeMatlSupplier" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Description</label>
                                        <input type="text" name="description" id="form-descriptionMaterial" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Type</label>
                                        <select name="type" id="form-typeMaterial" style="width: 100%;" class="form-control" required>
                                            <option value="">- Select Type -</option>
                                            <option value="RM">Raw Material</option>
                                            <option value="WIP">WIP Material</option>
                                            <option value="PRD">Product</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">For Packaging?</label>
                                        <select name="statusPackaging" id="form-statusPackaging" style="width: 100%;" class="form-control" required>
                                            <option value="">- Select Type -</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Flowmeter Feed</label>
                                        <input type="text" name="qtf_feed" id="form-qtfFeedMaterial" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Flowmeter Rundown</label>
                                        <input type="text" name="qtf_rundown" id="form-qtfRundownMaterial" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Yield (0-100%)</label>
                                        <input type="number" name="yield" id="form-yieldMaterial" style="width: 100%;" class="form-control col-sm-12" step="any" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-material">Save</button>
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
        var index_url   = "{{ route('materialsetup.index') }}";
        var post_url    = "{{ route('materialsetup.store') }}";
        var show_url    = "{{ route('materialsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_material            = '#form-material';

        const $txt_flagMaterial         = '#form-flagMaterial';
        const $txt_modeMaterial         = '#form-modeMaterial';
        const $txt_idMaterial           = '#form-idMaterial';

        const $txt_codeMaterial         = '#form-codeMaterial';
        const $txt_codeMaterialNonEudr  = '#form-codeMaterialNonEudr';
        const $txt_descriptionMaterial  = '#form-descriptionMaterial';
        const $txt_yieldMaterial        = '#form-yieldMaterial';
        const $cmb_typeMaterial         = '#form-typeMaterial';
        const $txt_qtfFeedMaterial      = '#form-qtfFeedMaterial';
        const $txt_qtfRundownMaterial   = '#form-qtfRundownMaterial';
        const $cmb_statusPackaging      = '#form-statusPackaging';
        const $txt_codeMaterialSupplier = '#form-codeMatlSupplier';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_modalMaterial();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_material).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_modeMaterial).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' MATERIAL?',
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

                                        $($dt_material).DataTable().ajax.reload();
                                        $($modal_material).modal('hide');

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
        function initialize_modalMaterial(){
            $($txt_codeMaterial).val('');
            $($txt_codeMaterialNonEudr).val('');
            $($txt_descriptionMaterial).val('');
            $($txt_yieldMaterial).val('');
            $($cmb_typeMaterial).val('');
            $($txt_qtfFeedMaterial).val('');
            $($txt_qtfRundownMaterial).val('');
            $($cmb_statusPackaging).val('');
            $($txt_codeMaterialSupplier).val('');
        };

</script>
@endpush

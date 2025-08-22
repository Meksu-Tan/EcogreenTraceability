supplier<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-supplier">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-headerSectionSupplier">Setup Supplier</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-supplier" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-flagSupplier" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-idSupplier" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-modeSupplier" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Supplier Code</label>
                                        <input type="number" name="code" id="form-codeSupplier" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Batch Code</label>
                                        <input type="text" name="batch_code" id="form-batchCode" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Supplier Description</label>
                                        <input type="text" name="description" id="form-descriptionSupplier" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group" hidden>
                                        <label for="name">Supplier Type</label>
                                        <select name="type" id="form-typeSupplier" style="width: 100%;" class="form-control" disabled>
                                            <option value="">- Select Type -</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-supplier">Save</button>
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
        var index_url   = "{{ route('suppliersetup.index') }}";
        var post_url    = "{{ route('suppliersetup.store') }}";
        var show_url    = "{{ route('suppliersetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_supplier            = '#form-supplier';

        const $txt_flagSupplier         = '#form-flagSupplier';
        const $txt_modeSupplier         = '#form-modeSupplier';
        const $txt_idSupplier           = '#form-idSupplier';

        const $txt_codeSupplier         = '#form-codeSupplier';
        const $txt_descriptionSupplier  = '#form-descriptionSupplier';
        const $cmb_typeSupplier         = '#form-typeSupplier';
        const $txt_batchCode            = '#form-batchCode';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_modalSupplier();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_supplier).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_modeSupplier).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' SUPPLIER?',
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

                                        $($dt_supplier).DataTable().ajax.reload();
                                        $($modal_supplier).modal('hide');

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
        function initialize_modalSupplier(){
            $($txt_codeSupplier).val('');
            $($txt_descriptionSupplier).val('');
            $($cmb_typeSupplier).val('');
            $($txt_batchCode).val('');
        };

</script>
@endpush

<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjustmentInitSupplier">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Add Supplier Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-adjustmentInitSupplier" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-adjustmentInitSupplier-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-adjustmentInitSupplier-idHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTail" id="form-adjustmentInitSupplier-idTail" class="form-control text-uppercase" required>
                                        <input type="hidden" name="mode" id="form-adjustmentInitSupplier-mode" class="form-control text-uppercase" required>
                                        <input type="hidden" name="adjNumber" id="form-adjustmentInitSupplier-entryNo" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTank" id="form-adjustmentInitSupplier-idTank" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entryDate" id="form-adjustmentInitSupplier-entryDate" class="form-control text-uppercase" required>
                                        <input type="hidden" name="materialDoc" id="form-adjustmentInitSupplier-materialDoc" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idMaterial" id="form-adjustmentInitSupplier-idMaterial" class="form-control text-uppercase" required>
                                        <input type="hidden" name="flagHead" id="form-adjustmentInitSupplier-flagHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="batchNo" id="form-adjustmentInitSupplier-batchNo" class="form-control text-uppercase" required>
                                        <input type="hidden" name="poNo" id="form-adjustmentInitSupplier-poNo" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Select Supplier</label>
                                        <select name="idSupplier" id="form-adjustmentInitSupplier-supplier" style="width: 100%;" class="form-control" required>
                                            <option value=''>- Select Supplier -</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Batch SAP - max. 11 character</label>
                                        <input type="text" name="batchSap" id="form-adjustmentInitSupplier-batchSap" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Entry Qty (MT)</label>
                                        <input type="number" name="qty" id="form-adjustmentInitSupplier-qty" class="form-control text-uppercase" step="any" required>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="insert-adjustmentInitSupplier">Insert Supplier</button>
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
        var index_url   = "{{ route('rmentry.index') }}";
        var post_url    = "{{ route('rmentry.store') }}";
        var show_url    = "{{ route('rmentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_adjustmentInitSupplier             = '#form-adjustmentInitSupplier';

        const $txt_adjustmentInitSupplier_flag         = '#form-adjustmentInitSupplier-flag';
        const $txt_adjustmentInitSupplier_mode         = '#form-adjustmentInitSupplier-mode';
        const $txt_adjustmentInitSupplier_idHead       = '#form-adjustmentInitSupplier-idHead';
        const $txt_adjustmentInitSupplier_idTail       = '#form-adjustmentInitSupplier-idTail';
        const $txt_adjustmentInitSupplier_number       = '#form-adjustmentInitSupplier-entryNo';
        const $txt_adjustmentInitSupplier_idTank       = '#form-adjustmentInitSupplier-idTank';
        const $txt_adjustmentInitSupplier_entryDate    = '#form-adjustmentInitSupplier-entryDate';
        const $txt_adjustmentInitSupplier_materialDoc  = '#form-adjustmentInitSupplier-materialDoc';
        const $txt_adjustmentInitSupplier_idMaterial   = '#form-adjustmentInitSupplier-idMaterial';
        const $txt_adjustmentInitSupplier_flagHead     = '#form-adjustmentInitSupplier-flagHead';

        const $txt_adjustmentInitSupplier_qty          = '#form-adjustmentInitSupplier-qty';
        const $cmb_adjustmentInitSupplier_name         = '#form-adjustmentInitSupplier-supplier';
        const $txt_adjustmentInitSupplier_batchSap     = '#form-adjustmentInitSupplier-batchSap';

        const $txt_adjustmentInitSupplier_batchNo      = '#form-adjustmentInitSupplier-batchNo';
        const $txt_adjustmentInitSupplier_poNo         = '#form-adjustmentInitSupplier-poNo';

        const $btn_adjustmentInitSupplier_insert       = '#insert-adjustmentInitSupplier';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_adjustmentInitSupplier).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_adjustmentInitSupplier_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' SUPPLIER ?',
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

                                        $($modal_adjustmentInit_supplier).modal('hide');

                                        initialize_modalAdjustmentInit(data.flag, data.mode, data.adjNumber, data.idHead, data.entryDate,
                                                                       data.idTank, data.materialDoc, data.idMaterial,
                                                                       data.batchNo, data.poNo);
                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });
            /* LISTENER ON CLICK FUNCTION */


        });

    /* FUNCTION AJAX */
        function ajax_cmbSupplier($tagId, value=null, text=null){
            $($tagId).empty();

            $($tagId).select2({
                placeholder: "(Option) Select Supplier...",
                minimumInputLength: 2,
                ajax: {
                    url: show_url,
                    dataType: 'json',
                    data: function (params) {
                        return {
                            flag: 'get_activeSupplier_bySelect2',
                            supplier: $.trim(params.term)
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                dropdownParent: $($modal_adjustmentInit_supplier)
            });

            if (value) {
                const option = new Option(text, value, true, true);
                $($tagId).append(option).trigger('change.select2');
            };
        };


    /* FUNCTION INIT */
        function initialize_modalAdjustmentInitSupplier($mode, $flagHead=null, $idHead=null, $idTail=null, $number=null, $qty=null,
                                                 $idsupplier=null, $supplier=null, $idTank=null, $entryDate=null,
                                                 $materialDoc=null, $batchSap=null, $idMaterial=null, $batchNo=null, $poNo=null){

            $($txt_adjustmentInitSupplier_flag).val('post_adjustmentInitSupplier');
            $($txt_adjustmentInitSupplier_mode).val($mode);
            $($txt_adjustmentInitSupplier_idHead).val($idHead);
            $($txt_adjustmentInitSupplier_idTail).val($idTail);
            $($txt_adjustmentInitSupplier_number).val($number);
            $($txt_adjustmentInitSupplier_qty).val($qty);
            $($txt_adjustmentInitSupplier_entryDate).val($entryDate);
            $($txt_adjustmentInitSupplier_idTank).val($idTank);
            $($txt_adjustmentInitSupplier_materialDoc).val($materialDoc);
            $($txt_adjustmentInitSupplier_batchSap).val($batchSap);
            $($txt_adjustmentInitSupplier_idMaterial).val($idMaterial);
            $($txt_adjustmentInitSupplier_flagHead).val($flagHead);
            $($txt_adjustmentInitSupplier_batchNo).val($batchNo);
            $($txt_adjustmentInitSupplier_poNo).val($poNo);

            if ($mode == 'ADD'){
                ajax_cmbSupplier($cmb_adjustmentInitSupplier_name);
            } else {
                ajax_cmbSupplier($cmb_adjustmentInitSupplier_name, $idsupplier, $supplier);
            }
        };

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

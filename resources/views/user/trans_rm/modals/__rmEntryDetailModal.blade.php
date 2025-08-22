<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-rm-entrySupplier">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Supplier Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-rmentrySupplier" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-rmentrySupplier-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-rmentrySupplier-idHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTail" id="form-rmentrySupplier-idTail" class="form-control text-uppercase" required>
                                        <input type="hidden" name="mode" id="form-rmentrySupplier-mode" class="form-control text-uppercase" required>
                                        <input type="hidden" name="rmNumber" id="form-rmentrySupplier-entryNo" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTank" id="form-rmentrySupplier-idTank" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entryDate" id="form-rmentrySupplier-entryDate" class="form-control text-uppercase" required>
                                        <input type="hidden" name="materialDoc" id="form-rmentrySupplier-materialDoc" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idMaterial" id="form-rmentrySupplier-idMaterial" class="form-control text-uppercase" required>
                                        <input type="hidden" name="po" id="form-rmentrySupplier-po" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Select Supplier</label>
                                        <select name="idSupplier" id="form-rmentrySupplier-supplier" style="width: 100%;" class="form-control" required>
                                            <option value=''>- Select Supplier -</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Batch SAP - auto</label>
                                        <input type="text" name="batchSap" id="form-rmentrySupplier-batchSap" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Entry Qty (MT)</label>
                                        <input type="number" name="qty" id="form-rmentrySupplier-qty" class="form-control text-uppercase" step="any" required>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="insert-rmentrySupplier">Insert Supplier</button>
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
        const $form_rmEntrySupplier             = '#form-rmentrySupplier';

        const $txt_rmEntrySupplier_flag         = '#form-rmentrySupplier-flag';
        const $txt_rmEntrySupplier_mode         = '#form-rmentrySupplier-mode';
        const $txt_rmEntrySupplier_idHead       = '#form-rmentrySupplier-idHead';
        const $txt_rmEntrySupplier_idTail       = '#form-rmentrySupplier-idTail';
        const $txt_rmEntrySupplier_number       = '#form-rmentrySupplier-entryNo';
        const $txt_rmEntrySupplier_idTank       = '#form-rmentrySupplier-idTank';
        const $txt_rmEntrySupplier_entryDate    = '#form-rmentrySupplier-entryDate';
        const $txt_rmEntrySupplier_materialDoc  = '#form-rmentrySupplier-materialDoc';
        const $txt_rmEntrySupplier_idMaterial   = '#form-rmentrySupplier-idMaterial';
        const $txt_rmEntrySupplier_po           = '#form-rmentrySupplier-po';

        const $txt_rmEntrySupplier_qty          = '#form-rmentrySupplier-qty';
        const $cmb_rmEntrySupplier_name         = '#form-rmentrySupplier-supplier';
        const $txt_rmEntrySupplier_batchSap     = '#form-rmentrySupplier-batchSap';

        const $btn_rmEntrySupplier_insert       = '#insert-rmentrySupplier';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_rmEntrySupplier).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_rmEntrySupplier_mode).val();

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

                                        $($modal_rmEntry_addSupplier).modal('hide');
                                        initialize_modalRmEntry(data.mode, data.rmNumber, data.idHead, data.entryDate,
                                                                data.idTank, data.materialDoc, data.idMaterial, data.po);
                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });
            /* LISTENER ON CLICK FUNCTION */

            /* LISTENER ON CHANGE FUNCTION */
                $($cmb_rmEntrySupplier_name).on('change', function(){
                    $idSupplier = $($cmb_rmEntrySupplier_name).val();
                    ajax_generateBatchCode($txt_rmEntrySupplier_batchSap, $idSupplier);
                });


        });

    /* FUNCTION AJAX */
        function ajax_generateBatchCode($id, $idSupplier){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_batchCode_bySupplier',
                    idSupplier: $idSupplier,
                },
                success: function(response){
                    var len = 0;

                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].batchCode);

                    } else {
                        $($id).val("");
                    }
                }
            });
        };
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
                dropdownParent: $($modal_rmEntry_addSupplier)
            });

            if (value) {
                const option = new Option(text, value, true, true);
                $($tagId).append(option).trigger('change.select2');
            };
        };


    /* FUNCTION INIT */
        function initialize_modalRmEntrySupplier($mode, $idHead=null, $idTail=null, $number=null, $qty=null,
                                                 $idsupplier=null, $supplier=null, $idTank=null, $entryDate=null,
                                                 $materialDoc=null, $batchSap=null, $idMaterial=null, $po=null){

            $($txt_rmEntrySupplier_flag).val('post_rmEntrySupplier');
            $($txt_rmEntrySupplier_mode).val($mode);
            $($txt_rmEntrySupplier_idHead).val($idHead);
            $($txt_rmEntrySupplier_idTail).val($idTail);
            $($txt_rmEntrySupplier_number).val($number);
            $($txt_rmEntrySupplier_qty).val($qty);
            $($txt_rmEntrySupplier_entryDate).val($entryDate);
            $($txt_rmEntrySupplier_idTank).val($idTank);
            $($txt_rmEntrySupplier_materialDoc).val($materialDoc);
            $($txt_rmEntrySupplier_batchSap).val($batchSap);
            $($txt_rmEntrySupplier_idMaterial).val($idMaterial);
            $($txt_rmEntrySupplier_po).val($po);

            if ($mode == 'ADD'){
                ajax_cmbSupplier($cmb_rmEntrySupplier_name);
            } else {
                ajax_cmbSupplier($cmb_rmEntrySupplier_name, $idsupplier, $supplier);
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

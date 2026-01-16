<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjustmentSupplier">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Supplier Adjustment Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-adjustmentSupplier" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-adjustmentSupplier-flag" class="form-control" required>
                                        <input type="hidden" name="idHead" id="form-adjustmentSupplier-idBalanceHead" class="form-control" required>
                                        <input type="hidden" name="entryNo" id="form-adjustmentSupplier-entryNo" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-adjustmentSupplier-mode" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Date (Auto Detect)</label>
                                                <input type="date" name="entryDate" id="form-adjustmentSupplier-entryDate" style="width: 100%;" class="form-control" required autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-10">
                                            <div class="form-group">
                                                <label for="name">Material</label>
                                                <select name="idMaterial" id="form-adjustmentSupplier-material" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Material -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="tank" id="form-adjustmentSupplier-tank" style="width: 100%;" class="form-control" required>
                                                    <option value="">- No Sloc -</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Adjustment Type</label>
                                                <select name="adjustType" id="form-adjustmentSupplier-adjustType" style="width: 100%;" class="form-control" required>
                                                    <option value="-">- Select Adjust Type -</option>
                                                    <option value="in">- Adjust IN -</option>
                                                    <option value="out">- Adjust OUT -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Supplier</label>
                                                <select name="idSupplier" id="form-adjustmentSupplier-idSupplier" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Supplier -</option>
                                                </select>
                                                <label for="name" id="form-adjustmentSupplier-currentQty">Stock : N/A</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Batch SAP</label>
                                                <select name="batchSap" id="form-adjustmentSupplier-batchSap" class="form-control" required>
                                                    <option value="">- Select Batch SAP -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Adjustment Qty (MT)</label>
                                                <input type="number" step="0.1" name="qty" id="form-adjustmentSupplier-qty" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary" id="save-adjustmentSupplier">
                                            Save Entry
                                        </button>
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
        const $form_adjustmentSupplier                 = '#form-adjustmentSupplier';

        const $txt_adjustmentSupplier_flag             = '#form-adjustmentSupplier-flag';
        const $txt_adjustmentSupplier_mode             = '#form-adjustmentSupplier-mode';
        const $txt_adjustmentSupplier_idBalanceHead    = '#form-adjustmentSupplier-idBalanceHead';
        const $txt_adjustmentSupplier_number           = '#form-adjustmentSupplier-entryNo';

        const $txt_adjustmentSupplier_date             = '#form-adjustmentSupplier-entryDate';
        const $txt_adjustmentSupplier_qty              = '#form-adjustmentSupplier-qty';
        const $txt_adjustmentSupplier_idMaterial       = '#form-adjustmentSupplier-material';
        const $txt_adjustmentSupplier_idTank           = '#form-adjustmentSupplier-tank';
        const $txt_adjustmentSupplier_idSupplier       = '#form-adjustmentSupplier-idSupplier';
        const $txt_adjustmentSupplier_batchSap         = '#form-adjustmentSupplier-batchSap';
        const $txt_adjustmentSupplier_adjustType       = '#form-adjustmentSupplier-adjustType';
        const $lbl_adjustmentSupplier_currentQty       = '#form-adjustmentSupplier-currentQty';

        const $btn_adjustmentSupplier_save             = '#save-adjustmentSupplier';

        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_modalAdjustmentSupplier();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_adjustmentSupplier).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_adjustmentSupplier_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' SUPPLIER ADJUSTMENT entry ?',
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

                                        $($modal_adjustmentSupplier).modal('hide');
                                        initialize_page();
                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });

                $('#modal-adjustmentSupplier').on('hidden.bs.modal', function () {
                    $('#form-adjustmentSupplier')[0].reset();
                    $('#form-adjustmentSupplier-idSupplier').val(null).trigger('change');
                    $('#form-adjustmentSupplier-material').empty().append('<option value="">- Select Material -</option>').val('').trigger('change');
                    $('#form-adjustmentSupplier-currentQty').val('');
                    $('#form-adjustmentSupplier-idMaterial').val('');
                    $('#form-adjustmentSupplier-adjustType').val('-').trigger('change');
                });
                $(document).on('change', '#form-adjustmentSupplier-material, #form-adjustmentSupplier-tank', function () {
                    let material = $($txt_adjustmentSupplier_idMaterial).val();
                    let tank     = $($txt_adjustmentSupplier_idTank).val();
                    let idHead   = $($txt_adjustmentSupplier_idBalanceHead).val();

                    if (material && tank) {
                        ajax_populateSupplierByFilter($txt_adjustmentSupplier_idSupplier);
                    } else {
                        $($txt_adjustmentSupplier_idSupplier).empty().append('<option value="">- Select Supplier -</option>').trigger('change');
                    }

                    $($txt_adjustmentSupplier_batchSap).empty().append('<option value="">- Select Batch SAP -</option>');
                });
                $(document).on('change', $txt_adjustmentSupplier_idSupplier, function () {
                    if (!$(this).hasClass("select2-hidden-accessible")) {
                        return;
                    }

                    let data = $(this).select2('data');
                    
                    if (data.length > 0) {
                        let qty = data[0].qty ?? 0;
                        $($lbl_adjustmentSupplier_currentQty).text("Stock (MT): " + parseFloat(qty).toFixed(3));
                        ajax_populateBatchBySupplier();
                    } else {
                        $($lbl_adjustmentSupplier_currentQty).text("Stock (MT): 0.000");
                    }
                });
        });

        function ajax_createAdjNumber($id){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_adjNewEntryNumber'
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].adj_number);
                        // ajax_getTotalQtySupplier($txt_adjustmentSupplier_qty, response['data'][0].adj_number, 'ADD');
                    }
                }
            });
        };
        function ajax_populateMaterial(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveMaterial',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_material;
                            var populate_2 = response['data'][i].material;

                            if (selectedValue) {
                                if (populate_1 == selectedValue) {
                                    var option = "<option value='"+populate_1+"' selected='"+selectedValue+"'>"+populate_2+"</option>";
                                } else {
                                    var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                                }
                            } else {
                                var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                            }
                            $(id).append(option);
                        }
                    }
                }
            });
        };
        function ajax_populateTank(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_tank;
                            var populate_2 = response['data'][i].tank;

                            if (selectedValue) {
                                if (populate_1 == selectedValue) {
                                    var option = "<option value='"+populate_1+"' selected='"+selectedValue+"'>"+populate_2+"</option>";
                                } else {
                                    var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                                }
                            } else {
                                var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                            }
                            $(id).append(option);
                        }
                    }
                }
            });
        };
        function ajax_populateSupplierByFilter($tagId, value=null, text=null){
            $($tagId).empty();

            $($tagId).select2({
                placeholder: "Select Supplier...",
                minimumInputLength: 0,
                ajax: {
                    url: show_url,
                    type: 'get',
                    dataType: 'json',
                    data: function (params) {
                        return {
                            flag: 'get_supplierByFilter',
                            idMaterial: $($txt_adjustmentSupplier_idMaterial).val(),
                            tank: $($txt_adjustmentSupplier_idTank).val()
                        };
                    },
                    processResults: function (response) {
                        let rows = Array.isArray(response.data) ? response.data : [];

                        return {
                            results: rows.map(item => ({
                                id: item.id_supplier,
                                text: item.supplier,
                                qty: item.total_qty
                            }))
                        };
                    }
                },
                dropdownParent: $('#modal-adjustmentSupplier')
            });
        };
        function ajax_populateBatchBySupplier(){
            let supplier = $($txt_adjustmentSupplier_idSupplier).val();

            if (!supplier) {
                $($txt_adjustmentSupplier_batchSap).empty().append('<option value="">- Select Batch SAP -</option>');
                return;
            }

            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data: {
                    flag: 'get_batchBySupplier',
                    idMaterial: $($txt_adjustmentSupplier_idMaterial).val(),
                    tank: $($txt_adjustmentSupplier_idTank).val(),
                    idSupplier: supplier
                },
                success: function(response){
                    let rows = Array.isArray(response.data) ? response.data : [];
                    let $batch = $($txt_adjustmentSupplier_batchSap);
                    $batch.empty().append('<option value="">- Select Batch SAP -</option>');

                    rows.forEach(r => {
                        $batch.append(
                            `<option value="${r.batch_sap}">
                                ${r.batch_sap} (Stock: ${parseFloat(r.qty).toFixed(3)} MT)
                            </option>`
                        );
                    });

                    if (rows.length > 0) {
                        $batch.val(rows[0].batch_sap).trigger('change');
                    }
                }
            });
        };

        function initialize_modalAdjustmentSupplier($flag, $mode, $idHead=null, $adj_number=null, $entryDate=null, $idMaterial=null, $idTank=null, $adjustType="-", $idSupplier=null, $batchSap=null){

            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($txt_adjustmentSupplier_flag).val($flag);
            $($txt_adjustmentSupplier_mode).val($mode);
            $($txt_adjustmentSupplier_idBalanceHead).val($idHead);
            $($txt_adjustmentSupplier_number).val($adj_number);
            $($txt_adjustmentSupplier_date).val($entryDate);
            $($txt_adjustmentSupplier_adjustType).val($adjustType);
            $($txt_adjustmentSupplier_batchSap).val($batchSap);

            if ($mode == 'ADD'){
                $($txt_adjustmentSupplier_qty).val('0');
                $($txt_adjustmentSupplier_date).val(currentDate);

                if ($flag == 'post_adjustmentSupplier'){
                    ajax_createAdjNumber($txt_adjustmentSupplier_number);
                }
            }

            ajax_populateMaterial($txt_adjustmentSupplier_idMaterial, $idMaterial);
            ajax_populateTank($txt_adjustmentSupplier_idTank, $idTank);
        }
</script>
@endpush
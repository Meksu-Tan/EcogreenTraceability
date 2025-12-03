<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-transferEntry">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Transfer Inter-Plant Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-transferEntry" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-transferEntry-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-transferEntry-idHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="supplierCode" id="form-transferEntry-supplierCode" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idSupplier" id="form-transferEntry-idSupplier" class="form-control text-uppercase" required>
                                        <input type="hidden" name="stockQtyDest" id="form-transferEntry-stockQtyDest" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-transferEntry-mode" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="name">Entry Number (Auto)</label>
                                                <input name="entry_no" id="form-transferEntry-entryNo" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="name">Date (Auto Detect)</label>
                                                <input type="date" name="entry_date" id="form-transferEntry-entryDate" style="width: 100%;" class="form-control" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Material Document (SAP)</label>
                                                <input name="material_doc" id="form-transferEntry-materialDoc" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label for="name">Transfer Material</label>
                                                <select name="id_material" id="form-transferEntry-material" style="width: 100%;" class="form-control" required>
                                                    <option value="-">- Select Material -</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <label for="name">Trf Type (-Trf ALL- only for TRF non-EOB1 to Adjust OUT)</label>
                                                <select name="trf_type" id="form-transferEntry-trfType" style="width: 100%;" class="form-control" required>
                                                    <option value="-">- Select Trf -</option>
                                                 
                                                    @if($idPlant == "1002")
                                                        <option value="in">- Trf IN to EOB1 -</option>
                                                        <option value="out">- Trf OUT from EOB1 -</option>
                                                    @elseif($idPlant == "1003")
                                                        <option value="in">- Trf IN to EOB2 -</option>
                                                        <option value="out">- Trf OUT from EOB2 -</option>
                                                    @elseif($idPlant == "1007")
                                                        <option value="in">- Trf IN to EOB3 -</option>
                                                        <option value="out">- Trf OUT from EOB3 -</option>
                                                    @else
                                                        <option value="in">- Trf IN to EOMB -</option>
                                                        <option value="out">- Trf OUT from EOMB -</option>
                                                    @endif

                                                    <option value="all">- Trf ALL -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-sloc" style="display:none">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Source SLoc (change this for TRF IN)</label>
                                                <select name="source_sloc" id="form-transferEntry-source" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Sloc -</option>
                                                </select>
                                                <label for="name" id="form-transferEntry-stock1">Stock : N/A</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Transfer SLoc (change this for TRF OUT)</label>
                                                <select name="trf_sloc" id="form-transferEntry-destination" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Sloc -</option>
                                                </select>
                                                <label for="name" id="form-transferEntry-stock2">Stock : N/A</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Trf Qty (MT)</label>
                                                <input name="trf_qty" id="form-transferEntry-qty" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-transferEntry">Save Entry</button>
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
        var index_url   = "{{ route('transfer.index') }}";
        var post_url    = "{{ route('transfer.store') }}";
        var show_url    = "{{ route('transfer.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
    const $form_transferEntry                   = '#form-transferEntry';

    const $form_transferEntry_flag              = '#form-transferEntry-flag';
    const $form_transferEntry_idHead            = '#form-transferEntry-idHead';
    const $form_transferEntry_mode              = '#form-transferEntry-mode';
    const $form_transferEntry_entryNo           = '#form-transferEntry-entryNo';
    const $form_transferEntry_qty               = '#form-transferEntry-qty';
    const $form_transferEntry_entryDate         = '#form-transferEntry-entryDate';
    const $form_transferEntry_materialDoc       = '#form-transferEntry-materialDoc';
    const $form_transferEntry_material          = '#form-transferEntry-material';
    const $form_transferEntry_source            = '#form-transferEntry-source';
    const $form_transferEntry_destination       = '#form-transferEntry-destination';
    const $form_transferEntry_transferType      = '#form-transferEntry-trfType';
    const $form_transferEntry_supplierCode      = '#form-transferEntry-supplierCode';
    const $form_transferEntry_idSupplier        = '#form-transferEntry-idSupplier';
    const $form_transferEntry_stockQtyDest      = '#form-transferEntry-stockQtyDest';
    const $lbl_transferEntry_stock1             = '#form-transferEntry-stock1';
    const $lbl_transferEntry_stock2             = '#form-transferEntry-stock2';

    const $div_sloc                             = '#div-sloc';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_transferEntry).unbind().on('submit', function(e) {
                    e.preventDefault();

                    $trfQty = $($form_transferEntry_qty).val();
                    if ($trfQty == 0){
                        Swal.fire({
                            position: 'top-end',
                            icon: 'warning',
                            title: 'Entry Qty!',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        return;
                    }
                    $idSupplier = $($form_transferEntry_idSupplier).val();
                    $trfType = $($form_transferEntry_transferType).val();

                    if ($idSupplier == 0){
                        if ($trfType == 'in'){
                            Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: 'Supplier Code!',
                                showConfirmButton: false,
                                timer: 1000
                            });
                            return;
                        } else if ($trf_type == 'out'){
                            if ($($form_transferEntry_stockQtyDest).val() != 0){
                                if ($($form_transferEntry_destination).val() != 10){
                                    Swal.fire({
                                        position: 'top-end',
                                        icon: 'warning',
                                        title: 'Stock Sloc Destination not zero!',
                                        showConfirmButton: false,
                                        timer: 1000
                                    });
                                    return;
                                }
                            }
                        } else if ($trf_type == 'all'){
                            if ($($form_transferEntry_stockQtyDest).val() != 0){
                                if ($($form_transferEntry_destination).val() != 10){
                                    Swal.fire({
                                        position: 'top-end',
                                        icon: 'warning',
                                        title: 'Stock Sloc Destination not zero!',
                                        showConfirmButton: false,
                                        timer: 1000
                                    });
                                    return;
                                }
                            }
                        }
                    }


                    var formData = new FormData(this);
                    var $mode = $($form_transferEntry_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' TRANSFER entry ?',
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

                                        $($modal_transferEntry).modal('hide');
                                        initialize_page();

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });

            /* LISTENER ON CLICK | CHANGE FUNCTION */
                $(document).on('change', $form_transferEntry_material, function(e){
                    e.preventDefault();
                    $mode = $($form_transferEntry_mode).val();
                    $trf_type = $($form_transferEntry_transferType).val();
                    $id_material = $($form_transferEntry_material).val();

                    if ($mode == 'ADD'){
                        ajax_createEntryNo($form_transferEntry_entryNo, $id_material);
                    }

                    if ($id_material == '-'){
                        $($div_sloc).hide();
                    } else {
                        if ($trf_type !== '-'){
                            if ($trf_type == 'in'){
                                ajax_populateTankRundown($form_transferEntry_source, $trf_type);
                                ajax_populateTankRundown($form_transferEntry_destination, $trf_type, $id_material);
                            } else if ($trf_type == 'out'){
                                ajax_populateTankRundown($form_transferEntry_source, $trf_type, $id_material);
                                ajax_populateTankRundown($form_transferEntry_destination, $trf_type);
                            } else if ($trf_type == 'all'){
                                ajax_populateTankRundown($form_transferEntry_source, $trf_type);
                                ajax_populateTankRundown($form_transferEntry_destination, $trf_type);
                            }
                        }
                    }
                });
                $(document).on('change', $form_transferEntry_transferType, function(e){
                    e.preventDefault();
                    $mode = $($form_transferEntry_mode).val();

                    if ($mode == 'ADD'){
                        $id_material = $($form_transferEntry_material).val();
                        $trf_type = $($form_transferEntry_transferType).val();

                        if ($id_material == '-'){
                            Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: 'Select Material!',
                                showConfirmButton: false,
                                timer: 1000
                            });
                            $($form_transferEntry_transferType).prop('selectedIndex',0);
                            return;
                        }

                        if ($trf_type !== '-'){
                            $($div_sloc).show();
                            if ($trf_type == 'in'){
                                ajax_populateTankRundown($form_transferEntry_source, $trf_type);
                                ajax_populateTankRundown($form_transferEntry_destination, $trf_type, $id_material);
                            } else if ($trf_type == 'out'){
                                ajax_populateTankRundown($form_transferEntry_source, $trf_type, $id_material);
                                ajax_populateTankRundown($form_transferEntry_destination, $trf_type);
                            } else if ($trf_type == 'all'){
                                ajax_populateTankRundown($form_transferEntry_source, $trf_type);
                                ajax_populateTankRundown($form_transferEntry_destination, $trf_type);
                            }
                        } else {
                            $($div_sloc).hide();
                        }

                    }
                });
                $(document).on('change', $form_transferEntry_destination, function(e){
                    e.preventDefault();

                    $idTank = $($form_transferEntry_destination).val();
                    ajax_getStockMaterial($lbl_transferEntry_stock2, $id_material, $idTank);
                });
                $(document).on('change', $form_transferEntry_source, function(e){
                    e.preventDefault();

                    $idTank = $($form_transferEntry_source).val();
                    $trfType = $($form_transferEntry_transferType).val();
                    ajax_getStockMaterial($lbl_transferEntry_stock1, $id_material, $idTank, $trfType);
                });

            /* LISTENER ON MODAL STACK */


        });

    /* FUNCTION AJAX */
        function ajax_cmbMaterial(id, selectedValue=null){
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
        function ajax_createEntryNo($id, $id_material){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_newTransferEntryNo',
                    id_material: $id_material
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].entryNo);
                    }
                }
            });
        };
        function ajax_populateTankRundown(id, $trfType, $idMaterial=null, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').remove();
            if ($idMaterial == null){
                $(id).append('<option value="">- Select Sloc -</option>');
            }
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank_rundown',
                    idMaterial: $idMaterial,
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

                        $($lbl_transferEntry_stock2).html("Stock (MT): N/A");
                        $($lbl_transferEntry_stock1).html("Stock (MT): N/A");
                        $($form_transferEntry_stockQtyDest).val(9999);

                        if ($trfType == 'in'){
                            if ($idMaterial !== null){
                                ajax_getStockMaterial($lbl_transferEntry_stock2, $id_material, response['data'][0].id_tank, $trfType);
                            }
                        } else {
                            if ($idMaterial !== null){
                                ajax_getStockMaterial($lbl_transferEntry_stock1, $id_material, response['data'][0].id_tank, $trfType);
                            }
                        }
                    }
                }
            });
        };
        function ajax_getStockMaterial($id, $idMaterial, $idTank, $trfType){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_totalStockMaterial',
                    idMaterial: $idMaterial,
                    idTank: $idTank
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        if ($trfType == 'in' && $trfType !== 'all'){
                            if (response['data'][0].total !== 'undefined'){
                                if ($idTank !== 5){
                                    if ($id == '#form-transferEntry-stock2'){
                                        $($id).html("AUTO IN/OUT");
                                    } else {
                                        $supplierCode = 'N/A';
                                        $($form_transferEntry_supplierCode).val($supplierCode);
                                        $($form_transferEntry_idSupplier).val(0);
                                        ajax_updateSupplierMaterial($id, $idMaterial, $idTank, $trfType, $supplierCode);
                                    }
                                } else {
                                    $($id).html("Stock (MT): " + response['data'][0].total);
                                    $($form_transferEntry_stockQtyDest).val(response['data'][0].total);
                                }
                            } else {
                                $($id).html("Stock (MT): " + response['data'][0].total);
                                $($form_transferEntry_stockQtyDest).val(response['data'][0].total);
                            }
                        } else {
                            $($id).html("Stock (MT): " + response['data'][0].total);
                            $($form_transferEntry_stockQtyDest).val(response['data'][0].total);
                        }
                    } else {
                        $($id).html("Stock (MT): N/A");
                        $($form_transferEntry_stockQtyDest).val(9999);
                    }
                }
            });
        };
        function ajax_updateSupplierMaterial($id, $idMaterial, $idTank, $trfType, $supplierCode){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_updateSupplierMaterial',
                    idMaterial: $idMaterial,
                    idTank: $idTank
                },
                success: function(response){
                    var len = 0;

                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $supplierCode = response['data'][0].supplierCode;
                        $idSupplier = response['data'][0].idSupplier;

                        $($form_transferEntry_supplierCode).val($supplierCode);
                        $($form_transferEntry_idSupplier).val($idSupplier);

                        $($id).html("AUTO IN/OUT - SUPPLIER MAT'L CODE : " + $supplierCode);

                    } else {
                        $($id).html("AUTO IN/OUT - SUPPLIER MAT'L CODE : " + $supplierCode);
                    }
                }
            });
        };

    /* FUNCTION INIT */
        function initializeTransferEntry($mode, $idHead=null, $idMaterial=null, $materialDoc=null, $trfType="-",
                                         $entryDate=null, $entryNo=null, $source=null, $destination=null, $trfQty=null){

            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($form_transferEntry_flag).val('post_transferEntry');
            $($form_transferEntry_mode).val($mode);
            $($form_transferEntry_idHead).val($idHead);
            $($form_transferEntry_materialDoc).val($materialDoc);
            $($form_transferEntry_entryNo).val($entryNo);
            $($form_transferEntry_transferType).val($trfType);
            $($form_transferEntry_idSupplier).val('');
            $($form_transferEntry_supplierCode).val('');

            ajax_cmbMaterial($form_transferEntry_material, $idMaterial);

            if ($mode == 'ADD'){
                $($form_transferEntry_qty).val('0');
                $($form_transferEntry_entryDate).val(currentDate);
            } else if ($mode == 'UPDATE'){
                $($form_transferEntry_qty).val($trfQty);
                $($form_transferEntry_entryDate).val($entryDate);
            }

            $($div_sloc).hide();
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


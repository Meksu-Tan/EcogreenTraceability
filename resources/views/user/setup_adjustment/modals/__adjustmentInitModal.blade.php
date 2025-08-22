<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjustmentInit">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Stock Initialization Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-adjustmentInit" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-adjustmentInit-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-adjustmentInit-idHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entry_no" id="form-adjustmentInit-entryNo" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-adjustmentInit-mode" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Date (Auto Detect)</label>
                                                <input type="date" name="entry_date" id="form-adjustmentInit-entryDate" style="width: 100%;" class="form-control" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Material Document (SAP)</label>
                                                <input name="material_doc" id="form-adjustmentInit-materialDoc" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="tank" id="form-adjustmentInit-tank" style="width: 100%;" class="form-control" required>
                                                    <option value="">- No Sloc -</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4" id="div-whx1" style="display:none">
                                            <div class="form-group">
                                                <label for="name">PO No</label>
                                                <input name="po_no" id="form-adjustmentInit-poNo" class="form-control text-uppercase">
                                            </div>
                                        </div>
                                        <div class="col-md-4" id="div-whx2" style="display:none">
                                            <div class="form-group">
                                                <label for="name">Batch No</label>
                                                <input name="batch_no" id="form-adjustmentInit-batchNo" class="form-control text-uppercase">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Material ( Do not change material selection after input supplier! )</label>
                                                <select name="idMaterial" id="form-adjustmentInit-material" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Material -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name" style="display: block;">&nbsp;</label>
                                                <button class="btn btn-dark" id="add-adjustmentInit-supplier">Add Supplier & Qty</button>
                                                <button class="btn btn-primary" id="save-adjustmentInit">Save Entry</button>
                                            </div>
                                        </div>
                                        <div class="col-md-5">

                                        </div>
                                        <div class="col-md-3" style="text-align:right">
                                            <div class="form-group">
                                                <label for="name">Total Qty (MT)</label>
                                                <input type="text" name="qty" id="form-adjustmentInit-qty" style="width: 100%; text-align:right" class="form-control col-sm-12" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable no-footer" id="form-adjustmentInit-detail" width="100%" role="grid" aria-describedby="table-1_info">
                                                <thead>
                                                    <tr>
                                                        <th width="7%">No</th>
                                                        <th>Action</th>
                                                        <th>Material</th>
                                                        <th>Supplier</th>
                                                        <th>Batch SAP</th>
                                                        <th>Qty (MT)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
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
        const $form_adjustmentInit                 = '#form-adjustmentInit';

        const $txt_adjustmentInit_flag             = '#form-adjustmentInit-flag';
        const $txt_adjustmentInit_mode             = '#form-adjustmentInit-mode';
        const $txt_adjustmentInit_idHead           = '#form-adjustmentInit-idHead';

        const $txt_adjustmentInit_number           = '#form-adjustmentInit-entryNo';
        const $txt_adjustmentInit_date             = '#form-adjustmentInit-entryDate';
        const $txt_adjustmentInit_qty              = '#form-adjustmentInit-qty';
        const $txt_adjustmentInit_material         = '#form-adjustmentInit-material';
        const $tbl_adjustmentInit_detail           = '#form-adjustmentInit-detail';
        const $cmb_adjustmentInit_tank             = '#form-adjustmentInit-tank';
        const $txt_adjustmentInit_materialDoc      = '#form-adjustmentInit-materialDoc';
        const $txt_adjustmentInit_poNo             = '#form-adjustmentInit-poNo';
        const $txt_adjustmentInit_batchNo          = '#form-adjustmentInit-batchNo';

        const $btn_adjustmentInit_addSupplier      = '#add-adjustmentInit-supplier';
        const $btn_adjustmentInit_save             = '#save-adjustmentInit';

        const $btn_adjustmentInit_deleteSupplier   = '#destroy-adjustmentInit-supplier';
        const $btn_adjustmentInit_updateSupplier   = '#update-adjustmentInit-supplier';

        const $div_whxEntry1                        = '#div-whx1';
        const $div_whxEntry2                        = '#div-whx2';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_adjustmentInit).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_adjustmentInit_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' ADJUSTMENT entry ?',
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

                                        $($modal_adjustmentInit).modal('hide');
                                        initialize_page();

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });
            /* LISTENER ON CLICK FUNCTION */
                $(document).on('click', $btn_adjustmentInit_addSupplier, function(e){
                    e.preventDefault();

                    var $idMaterial = $($txt_adjustmentInit_material).val();
                    if ($idMaterial == '' || $idMaterial == null){
                        Swal.fire({
                            position: 'top-end',
                            icon: 'warning',
                            title: 'Select Material!',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        return;
                    }

                    $($modal_adjustmentInit_supplier).modal('show');
                    var $mode = $($txt_adjustmentInit_mode).val();
                    var $idHead = $($txt_adjustmentInit_idHead).val();
                    var $idTail = null;
                    var $number = $($txt_adjustmentInit_number).val();
                    var $qty = null;
                    var $supplier = null;
                    var $idSupplier = null;
                    var $idTank = $($cmb_adjustmentInit_tank).val();
                    var $entryDate = $($txt_adjustmentInit_date).val();
                    var $materialDoc = $($txt_adjustmentInit_materialDoc).val();
                    var $batchSap = null;
                    var $flagHead = $($txt_adjustmentInit_flag).val();
                    var $batchNo = $($txt_adjustmentInit_batchNo).val();
                    var $poNo = $($txt_adjustmentInit_poNo).val();

                    initialize_modalAdjustmentInitSupplier($mode, $flagHead, $idHead, $idTail, $number, $qty, $idSupplier, $supplier, $idTank,
                                                           $entryDate, $materialDoc, $batchSap, $idMaterial, $batchNo, $poNo);
                });
                $(document).on('click', $btn_adjustmentInit_deleteSupplier, function(e){
                    e.preventDefault();
                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete this data',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes!'
                    }).then((willDeleted) => {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (willDeleted.value) {
                            ajax_deleteSupplier($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                $(document).on('click', $btn_adjustmentInit_updateSupplier, function(e){
                    /* ON UPDATE FROM BALANCE_TABLE */
                    e.preventDefault();
                    var $qty = $(this).attr('data-qty');
                    var $idSupplier = $(this).attr('data-idSupplier');
                    var $supplier = $(this).attr('data-supplier');
                    var $idTail = $(this).attr('data-idTail');
                    var $batchSap = $(this).attr('data-batchSap');

                    $($modal_adjustmentInit_supplier).modal('show');
                    var $mode = $($txt_adjustmentInit_mode).val();
                    var $idHead = $($txt_adjustmentInit_idHead).val();
                    var $number = $($txt_adjustmentInit_number).val();
                    var $qty = $qty;
                    var $idTank = $($cmb_adjustmentInit_tank).val();
                    var $entryDate = $($txt_adjustmentInit_date).val();
                    var $materialDoc = $($txt_adjustmentInit_materialDoc).val();
                    var $idMaterial = $($txt_adjustmentInit_material).val();

                    initialize_modalAdjustmentInitSupplier($mode, $idHead, $idTail, $number, $qty, $idSupplier, $supplier,
                                                           $idTank, $entryDate, $materialDoc, $batchSap, $idMaterial);
                });

            /* LISTENER ON MODAL STACK */
                $($modal_adjustmentInit_supplier).on('show.bs.modal', function () {
                    if ( $($modal_adjustmentInit).hasClass('show') ) {
                        $($modal_adjustmentInit).css('opacity', 0.3);
                    }
                });
                $($modal_adjustmentInit_supplier).on('hidden.bs.modal', function () {
                    if ( $($modal_adjustmentInit).hasClass('show') ) {
                        $($modal_adjustmentInit).css('opacity', 1);
                    }
                });

        });

    /* FUNCTION AJAX */
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
                        ajax_dtSupplierList($tbl_adjustmentInit_detail, response['data'][0].adj_number, 'ADD');
                        ajax_getTotalQtySupplier($txt_adjustmentInit_qty, response['data'][0].adj_number, 'ADD');
                    }
                }
            });
        };
        function ajax_createAdjNumberWhx($id){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_adjNewEntryNumberWhx'
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].adj_number);

                        ajax_dtSupplierList($tbl_adjustmentInit_detail, response['data'][0].adj_number, 'ADD');
                        ajax_getTotalQtySupplier($txt_adjustmentInit_qty, response['data'][0].adj_number, 'ADD');
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
        function ajax_populateMaterialWhx(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveMaterialWhx',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_materialpck;
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
        function ajax_populateWhx(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveWhx',
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
        function ajax_dtSupplierList($id, $number, $mode){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtSupplierList',
                        number: $number,
                        mode: $mode
                    }
                },
                order: [[ 0, 'desc']],
                responsive: true,
                columnDefs: [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'50%'},
                    { data: 'batch_sap', name: 'batch_sap', className: 'text-center'},
                    { data: 'qty', name: 'qty', className: 'text-right'}
                ]
            });
        };
        function ajax_getTotalQtySupplier($id, $number, $mode){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_totalQtySupplier',
                    number: $number,
                    mode: $mode
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].total);
                    }
                }
            });
        };
        function ajax_deleteSupplier($href){
            $.ajax({
                url: $href,
                type: "POST",
                data: {
                    '_method': 'DELETE'
                },
                success: function(data) {
                    if (data.status == 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 500
                        })
                        ajax_dtSupplierList($tbl_adjustmentInit_detail, $($txt_adjustmentInit_number).val(), 'ADD');
                        ajax_getTotalQtySupplier($txt_adjustmentInit_qty, $($txt_adjustmentInit_number).val(), 'ADD');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        })
                    }
                }
            })
        };

    /* FUNCTION INIT */
        function initialize_modalAdjustmentInit($flag, $mode, $adj_number=null, $idHead=null, $entryDate=null,
                                                $idTank=null, $materialDoc=null, $idMaterial=null,
                                                $batchNo=null, $poNo=null){

            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($txt_adjustmentInit_flag).val($flag);
            $($txt_adjustmentInit_mode).val($mode);
            $($txt_adjustmentInit_idHead).val($idHead);
            $($txt_adjustmentInit_number).val($adj_number);
            $($txt_adjustmentInit_materialDoc).val($materialDoc);
            $($txt_adjustmentInit_batchNo).val($batchNo);
            $($txt_adjustmentInit_poNo).val($poNo);

            if ($flag == 'post_adjustmentInit'){
                ajax_populateMaterial($txt_adjustmentInit_material, $idMaterial);
                ajax_populateTank($cmb_adjustmentInit_tank, $idTank);
                $($div_whxEntry1).hide();
                $($div_whxEntry2).hide();
                $($txt_adjustmentInit_poNo).prop('required', false);
                $($txt_adjustmentInit_batchNo).prop('required', false);
            } else if ($flag == 'post_adjustmentInitWhx'){
                ajax_populateMaterialWhx($txt_adjustmentInit_material, $idMaterial);
                ajax_populateWhx($cmb_adjustmentInit_tank, $idTank);
                $($div_whxEntry1).show();
                $($div_whxEntry2).show();
                $($txt_adjustmentInit_poNo).prop('required', true);
                $($txt_adjustmentInit_batchNo).prop('required', true);
            }

            if ($mode == 'ADD'){
                $($txt_adjustmentInit_qty).val('0');
                $($txt_adjustmentInit_date).val(currentDate);

                if ($flag == 'post_adjustmentInit'){
                    ajax_createAdjNumber($txt_adjustmentInit_number);
                } else if ($flag == 'post_adjustmentInitWhx'){
                    ajax_createAdjNumberWhx($txt_adjustmentInit_number);
                }
                ajax_getTotalQtySupplier($txt_adjustmentInit_qty, $adj_number, $mode);

            } else if ($mode == 'UPDATE'){
                $($txt_adjustmentInit_number).val($adj_number);
                $($txt_adjustmentInit_date).val($entryDate);

                ajax_dtSupplierList($tbl_adjustmentInit_detail, $idHead, $mode);
                ajax_getTotalQtySupplier($txt_adjustmentInit_qty, $idHead, $mode);
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

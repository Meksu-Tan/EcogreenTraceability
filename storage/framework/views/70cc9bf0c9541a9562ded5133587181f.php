<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-rm-entry">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Raw Material Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-rmentry" method="post">
                                    <?php echo csrf_field(); ?>
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-rmentry-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-rmentry-idHead" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-rmentry-mode" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Entry Number (Auto)</label>
                                                <input name="entry_no" id="form-rmentry-entryNo" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Date (Auto Detect)</label>
                                                <input type="date" name="entry_date" id="form-rmentry-entryDate" style="width: 100%;" class="form-control" required autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="tank" id="form-rmentry-tank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Sloc -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Material Document (SAP)</label>
                                                <input name="material_doc" id="form-rmentry-materialDoc" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Purchase Order (PO)</label>
                                                <input name="po" id="form-rmentry-po" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Material ( Do not change material selection after input supplier! )</label>
                                                <select name="idMaterial" id="form-rmentry-material" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Material -</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Specific Sloc No</label>
                                                <select name="tankNo[]" id="form-rmentry-tankNo" class="form-control" style="width: 100%;" multiple="multiple">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name" style="display: block;">&nbsp;</label>
                                                <button class="btn btn-dark" id="add-rmentry-supplier">Add Supplier & Qty</button>
                                                <button class="btn btn-primary" id="save-rmentry">Save Entry</button>
                                            </div>
                                        </div>
                                        <div class="col-md-5">

                                        </div>
                                        <div class="col-md-3" style="text-align:right">
                                            <div class="form-group">
                                                <label for="name">Total Qty (MT)</label>
                                                <input type="text" name="qty" id="form-rmentry-qty" style="width: 100%; text-align:right" class="form-control col-sm-12" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable no-footer" id="form-rmentry-detail" width="100%" role="grid" aria-describedby="table-1_info">
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
<?php $__env->startPush('js'); ?>
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "<?php echo e(route('rmentry.index')); ?>";
        var post_url    = "<?php echo e(route('rmentry.store')); ?>";
        var show_url    = "<?php echo e(route('rmentry.show', ':id')); ?>";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_rmEntry                 = '#form-rmentry';

        const $txt_rmEntry_flag             = '#form-rmentry-flag';
        const $txt_rmEntry_mode             = '#form-rmentry-mode';
        const $txt_rmEntry_idHead           = '#form-rmentry-idHead';

        const $txt_rmEntry_number           = '#form-rmentry-entryNo';
        const $txt_rmEntry_date             = '#form-rmentry-entryDate';
        const $txt_rmEntry_qty              = '#form-rmentry-qty';
        const $txt_rmEntry_material         = '#form-rmentry-material';
        const $tbl_rmEntry_detail           = '#form-rmentry-detail';
        const $cmb_rmEntry_tank             = '#form-rmentry-tank';
        const $txt_rmEntry_materialDoc      = '#form-rmentry-materialDoc';
        const $txt_rmEntry_po               = '#form-rmentry-po';
        const $cmb_rmEntry_tankNo           = '#form-rmentry-tankNo';

        const $btn_rmEntry_addSupplier      = '#add-rmentry-supplier';
        const $btn_rmEntry_save             = '#save-rmentry';

        const $btn_rmEntry_deleteSupplier   = '#destroy-rmentry-supplier';
        const $btn_rmEntry_updateSupplier   = '#update-rmentry-supplier';

        let rmEntry_selectedTankTails = [];

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_rmEntry).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_rmEntry_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' RM entry ?',
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

                                        $($modal_rmEntry).modal('hide');
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
                $(document).on('click', $btn_rmEntry_addSupplier, function(e){
                    e.preventDefault();

                    $($modal_rmEntry_addSupplier).modal('show');
                    var $mode = $($txt_rmEntry_mode).val();
                    var $idHead = $($txt_rmEntry_idHead).val();
                    var $idTail = null;
                    var $number = $($txt_rmEntry_number).val();
                    var $qty = null;
                    var $supplier = null;
                    var $idSupplier = null;
                    var $idTank = $($cmb_rmEntry_tank).val();
                    var $entryDate = $($txt_rmEntry_date).val();
                    var $materialDoc = $($txt_rmEntry_materialDoc).val();
                    var $batchSap = null;
                    var $idMaterial = $($txt_rmEntry_material).val();
                    var $po = $($txt_rmEntry_po).val();

                    initialize_modalRmEntrySupplier($mode, $idHead, $idTail, $number, $qty, $idSupplier, $supplier, $idTank,
                                                    $entryDate, $materialDoc, $batchSap, $idMaterial, $po);
                });
                $(document).on('click', $btn_rmEntry_deleteSupplier, function(e){
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
                $(document).on('click', $btn_rmEntry_updateSupplier, function(e){
                    /* ON UPDATE FROM BALANCE_TABLE */
                    e.preventDefault();
                    var $qty = $(this).attr('data-qty');
                    var $idSupplier = $(this).attr('data-idSupplier');
                    var $supplier = $(this).attr('data-supplier');
                    var $idTail = $(this).attr('data-idTail');
                    var $batchSap = $(this).attr('data-batchSap');

                    $($modal_rmEntry_addSupplier).modal('show');
                    var $mode = $($txt_rmEntry_mode).val();
                    var $idHead = $($txt_rmEntry_idHead).val();
                    var $number = $($txt_rmEntry_number).val();
                    var $qty = $qty;
                    var $idTank = $($cmb_rmEntry_tank).val();
                    var $entryDate = $($txt_rmEntry_date).val();
                    var $materialDoc = $($txt_rmEntry_materialDoc).val();

                    initialize_modalRmEntrySupplier($mode, $idHead, $idTail, $number, $qty, $idSupplier, $supplier, $idTank, $entryDate, $materialDoc, $batchSap);
                });

            /* LISTENER ON MODAL STACK */
                $($modal_rmEntry_addSupplier).on('show.bs.modal', function () {
                    if ( $($modal_rmEntry).hasClass('show') ) {
                        $($modal_rmEntry).css('opacity', 0.3);
                    }
                });
                $($modal_rmEntry_addSupplier).on('hidden.bs.modal', function () {
                    if ( $($modal_rmEntry).hasClass('show') ) {
                        $($modal_rmEntry).css('opacity', 1);
                    }
                    if (rmEntry_selectedTankTails.length > 0) {
                        $($cmb_rmEntry_tankNo).val(rmEntry_selectedTankTails).trigger('change');
                    }
                });
                $($modal_rmEntry).on('hidden.bs.modal', function () {
                    rmEntry_selectedTankTails = [];
                    $($cmb_rmEntry_tankNo).val(null).trigger('change');
                });
                $(document).on('change', $cmb_rmEntry_tankNo, function () {
                    rmEntry_selectedTankTails = $(this).val() || [];
                });

        });

    /* FUNCTION AJAX */
        function ajax_createRmNumber($id){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_rmNewEntryNumber'
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].rm_number);
                        ajax_dtSupplierList($tbl_rmEntry_detail, response['data'][0].rm_number, 'ADD');
                        ajax_getTotalQtySupplier($txt_rmEntry_qty, response['data'][0].rm_number, 'ADD');
                    }
                }
            });
        };
        function ajax_populateTank(id, selectedValue=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
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
        function ajax_populateTankNo(id, sloc=null, selectedValues=null, options={}) {
            const $select = $(id);

            // Normalize selected values to always array of strings
            let selected = [];

            if (Array.isArray(selectedValues)) {
                selected = selectedValues.map(String);
            } else if (typeof selectedValues === 'string') {
                try {
                    selected = JSON.parse(selectedValues).map(String);
                } catch {
                    selected = [];
                }
            }

            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data: {
                    flag: 'get_cmbActiveSpecificSourceTank',
                    sloc: sloc
                },
                success: function (response) {
                    $select.empty();

                    if (response.data && response.data.length) {
                        response.data.forEach(row => {
                            const isSelected = selected.includes(String(row.id_tank_tail));

                            const option = new Option(
                                row.tankNo,
                                row.id_tank_tail,
                                isSelected,
                                isSelected
                            );
                            
                            $select.append(option);
                        });
                    }
            
                    // Init Select2 only once
                    if (!$select.hasClass('select2-hidden-accessible')) {
                        $select.select2({
                            placeholder: ' - Select Specific Sloc No -',
                            closeOnSelect: false,
                            allowClear: true,
                            width: '100%',
                            dropdownParent: options.dropdownParent || $select.closest('.modal')
                        });
                    }

                    $select.trigger('change');
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
                        initialize_modalRmEntry($($txt_rmEntry_mode).val(), $($txt_rmEntry_number).val());
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

    /* FUNCTION INIT */
        function initialize_modalRmEntry($mode, $rm_number=null, $idHead=null, $entryDate=null,
                                         $idTank=null, $materialDoc=null, $idMaterial=null, $idTankTail=null, $po=null){

            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($txt_rmEntry_flag).val('post_rmEntry');
            $($txt_rmEntry_mode).val($mode);
            $($txt_rmEntry_idHead).val($idHead);
            $($txt_rmEntry_number).val($rm_number);
            $($txt_rmEntry_materialDoc).val($materialDoc);
            $($txt_rmEntry_po).val($po);
            ajax_populateTank($cmb_rmEntry_tank, $idTank);
            ajax_populateMaterial($txt_rmEntry_material, $idMaterial);

            if ($mode == 'ADD'){
                $($txt_rmEntry_qty).val('0');
                $($txt_rmEntry_date).val(currentDate);

                ajax_createRmNumber($txt_rmEntry_number);
                ajax_getTotalQtySupplier($txt_rmEntry_qty, $rm_number, $mode);

            } else if ($mode == 'UPDATE'){
                $($txt_rmEntry_number).val($rm_number);
                $($txt_rmEntry_date).val($entryDate);

                ajax_dtSupplierList($tbl_rmEntry_detail, $idHead, $mode);
                ajax_getTotalQtySupplier($txt_rmEntry_qty, $idHead, $mode);
            }

            if ($mode === 'ADD' && rmEntry_selectedTankTails.length === 0) {
                ajax_populateTankNo($cmb_rmEntry_tankNo, 4, $idTankTail);
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
<?php $__env->stopPush(); ?>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/user/trans_rm/modals/__rmEntryModal.blade.php ENDPATH**/ ?>
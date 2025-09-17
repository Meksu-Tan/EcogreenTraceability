@extends('layouts.app_user')
@section('title', 'Stock On-Hand (WIP / Warehouse)')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">On-Hand Report Type</label>
                                    <select name="reportType" id="reportType" style="width: 100%;" class="form-control" required>
                                        <option value="detail">- Detail Per Material -</option>
                                        <option value="summary">- Summary All Material -</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="div-detailOnHand">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">Start Date </label>
                                    <input type="date" name="startDate" id="startDate" style="width: 100%;" class="form-control col-sm-12" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">End Date </label>
                                    <input type="date" name="endDate" id="endDate" style="width: 100%;" class="form-control col-sm-12" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">Type </label>
                                    <select name="stockType" id="stockType" style="width: 100%;" class="form-control" required>
                                        <option value="WIP">WIP</option>
                                        <option value="WH">WH</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Material </label>
                                    <select name="materialStock" id="materialStock" style="width: 100%;" class="form-control" required>

                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">Sloc </label>
                                    <select name="sloc" id="sloc" style="width: 100%;" class="form-control" required>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="div-summaryOnHand" style="display:none">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">Start Date </label>
                                    <input type="date" name="stockDateStart" id="stockDateStart" style="width: 100%;" class="form-control col-sm-12" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">End Date </label>
                                    <input type="date" name="stockDateEnd" id="stockDateEnd" style="width: 100%;" class="form-control col-sm-12" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">Sloc </label>
                                    <select name="stockSloc" id="stockSloc" style="width: 100%;" class="form-control" required>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="name">Sloc - Type </label>
                                    <select name="slocType" id="sloc-type" style="width: 100%;" class="form-control" required>
                                        <option value='SUMMARY_WIP'>WIP</option>
                                        <option value='SUMMARY_WH'>WAREHOUSE</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div style="padding-left:15px; padding-bottom:10px">
                                <button type="button" id="viewStock" class="btn btn-primary" style="color:white"><i class="fas fa-globe" aria-hidden="true"></i> View </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card" id="div-rm" style="display:none">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableStockRm-storage" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Stock Date</th>
                                            <th>Description</th>
                                            <th>In (MT)</th>
                                            <th>Sloc</th>
                                            <th>Out (MT)</th>
                                            <th>Balance Material (MT)</th>
                                            <th>Balance Supplier (MT)</th>
                                            <th>ID / Batch SAP / Balance (MT) / Trace</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card" id="div-stock">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableStock" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Stock Date</th>
                                            <th>Description</th>
                                            <th>In (MT)</th>
                                            <th>Sloc</th>
                                            <th>Out (MT)</th>
                                            <th>Balance Material (MT)</th>
                                            <th>Balance Supplier (MT)</th>
                                            <th>ID / Supplier / Batch SAP / Balance (MT) / Trace</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card" style="display:none" id="div-stockSummary">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableStockSummary" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Stock Date</th>
                                            <th>Description</th>
                                            <th>Init Bal (MT)</th>
                                            <th>Total In (MT)</th>
                                            <th>Sloc</th>
                                            <th>Total Out (MT)</th>
                                            <th>Bal Material (MT)</th>
                                            <th>Bal Supplier (MT)</th>
                                            <th>ID / Supplier / Batch SAP / Balance (MT) / Trace</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('stock.index') }}";
        var post_url    = "{{ route('stock.store') }}";
        var show_url    = "{{ route('stock.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */

        const $startDate            = '#startDate';
        const $endDate              = '#endDate';
        const $materialStock        = '#materialStock';
        const $viewStock            = '#viewStock';
        const $tableStock           = '#tableStock';
        const $tableStockRm         = '#tableStockRm-storage';
        const $tableStockSummary    = '#tableStockSummary';
        const $sloc                 = '#sloc';
        const $onHandType           = '#reportType';
        const $stockType            = '#stockType';

        const $stockSloc            = '#stockSloc';
        const $stockDateStart       = '#stockDateStart';
        const $stockDateEnd         = '#stockDateEnd';
        const $slocType             = '#sloc-type';

        const $divRm                = '#div-rm';
        const $divStock             = '#div-stock';
        const $divStockSummary      = '#div-stockSummary';
        const $divDetailOnHand      = '#div-detailOnHand';
        const $divSummaryOnHand     = '#div-summaryOnHand';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */
                $(document).on('change', $materialStock, function(){
                    var $stockDat = $($materialStock + ' option:selected').text();

                    if ($stockDat.includes("/")) {
                        var $stockType = $stockDat.split("/")[1].trim();

                        if ($stockType == 'RM)'){
                            $($divRm).show();
                            $($tableStockRm).DataTable().clear().destroy();
                        } else {
                            $($divRm).hide();
                        };
                    } else {
                        $($divRm).hide();
                    };
                    $($tableStock).DataTable().clear().destroy();
                });
                $(document).on('change', $onHandType, function(){
                    $d_onHandType = $($onHandType).val();

                    $($divSummaryOnHand).hide();
                    $($divDetailOnHand).hide();
                    $($divStock).hide();
                    $($divStockSummary).hide();

                    if ($d_onHandType == 'detail'){
                        $($divDetailOnHand).show();
                        $($divStock).show();
                    } else if ($d_onHandType == 'summary'){
                        $($divSummaryOnHand).show();
                        $($divStockSummary).show();
                    }
                });
                $(document).on('change', $stockSloc, function(){
                    $d_idPlant = $($stockSloc).val();
                    dyn_populateSlocType($slocType, $d_idPlant);
                });

            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $viewStock, function(){
                    $d_onHandType = $($onHandType).val();

                    if ($d_onHandType == 'detail'){
                        $d_startDate = $($startDate).val();
                        $d_endDate = $($endDate).val();
                        $d_idMaterial = $($materialStock).val();
                        $d_idSloc = $($sloc).val();

                        if ($d_idMaterial == ''){
                            Swal.fire({
                                    position: 'top-end',
                                    icon: 'warning',
                                    title: 'Select Material!',
                                    showConfirmButton: false,
                                    timer: 1500
                            });
                            return;
                        }

                        ajax_populateDtStock($tableStock, 'NORMAL', $d_startDate, $d_endDate, $d_idMaterial, $d_idSloc);
                        ajax_populateDtStock($tableStockRm, 'STORAGE', $d_startDate, $d_endDate, $d_idMaterial, $d_idSloc);

                    } else if ($d_onHandType == 'summary'){
                        $d_stockDateStart = $($stockDateStart).val();
                        $d_stockDateEnd = $($stockDateEnd).val();
                        $d_stockSloc = $($stockSloc).val();
                        $d_stockSlocType = $($slocType).val();

                        ajax_populateDtStockSummary($tableStockSummary, $d_stockSlocType, $d_stockDateStart, $d_stockDateEnd, $d_stockSloc);
                    }
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */
        function populate_cmbMaterial($tagId, selectedValue=null){
            $($tagId).empty();

            $($tagId).select2({
                placeholder: "(Option) Select Material...",
                minimumInputLength: 2,
                ajax: {
                    url: show_url,
                    dataType: 'json',
                    data: function (params) {
                        return {
                            flag: 'get_activeMaterial_bySelect2',
                            materialStock: $.trim(params.term),
                            stockType: $($stockType).val()
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });
        };


    /* FUNCTION AJAX */
        function ajax_populateMaterialStock($tagId, selectedValue=null){
            // Empty the dropdown
            $($tagId).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_activeMaterialStock',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        // Read data and create <option >
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
                            $($tagId).append(option);
                        }
                    }
                }
            });
        };
        function ajax_populateDtStock($id, $mode, $d_startDate, $d_endDate=null, $d_idMaterial=null, $d_idSloc=null){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtStock',
                        mode: $mode,
                        startDate: $d_startDate,
                        endDate: $d_endDate,
                        idMaterial: $d_idMaterial,
                        idSloc: $d_idSloc
                    }
                },
                order: [[ 0, 'asc']],
                responsive: true,
                columnDefs: [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    targets: [6,7], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        // Convert in_qty and balance_supplier to floats
                        const balance = parseValue(rowData.balance);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        if (balance - balanceSupplier === 0 ) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'description', name: 'description', className: 'text-center' },
                    { data: 'in', name: 'in', className: 'text-right'},
                    { data: 'sloc', name: 'sloc', className: 'text-center' },
                    { data: 'out', name: 'out', className: 'text-right'},
                    { data: 'balance', name: 'balance', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'},
                ]
            });
        };
        function ajax_populateSloc($tagId, selectedValue=null){
            // Empty the dropdown
            $($tagId).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_activeSloc',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        // Read data and create <option >
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_plant;
                            var populate_2 = response['data'][i].description;
                            if (selectedValue) {
                                if (populate_1 == selectedValue) {
                                    var option = "<option value='"+populate_1+"' selected='"+selectedValue+"'>"+populate_2+"</option>";
                                } else {
                                    var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                                }
                            } else {
                                var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                            }
                            $($tagId).append(option);
                        }
                    }
                }
            });
        };
        function ajax_populateDtStockSummary($id, $mode, $d_stockDateStart, $d_stockDateEnd, $d_stockSloc){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtStockSummary',
                        mode: $mode,
                        stockDateStart: $d_stockDateStart,
                        stockDateEnd: $d_stockDateEnd,
                        idSloc: $d_stockSloc
                    }
                },
                order: [[ 0, 'asc']],
                responsive: true,
                columnDefs: [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                }, {
                    targets: [7,8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        // Convert in_qty and balance_supplier to floats
                        const lastBalance = parseValue(rowData.last_balance);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        if (lastBalance - balanceSupplier === 0 ) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left' },
                    { data: 'init_balance', name: 'init_balance', className: 'text-right'},
                    { data: 'in', name: 'in', className: 'text-right'},
                    { data: 'sloc', name: 'sloc', className: 'text-center' },
                    { data: 'out', name: 'out', className: 'text-right'},
                    { data: 'last_balance', name: 'last_balance', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        };

    /* FUNCTION DYNAMICS */
        function dyn_populateSlocType($tagId, $mode){
            $($tagId).find('option').remove();

            if ($mode == '1002'){
                var option1 = "<option value='SUMMARY_WIP'>WIP</option>";
                var option2 = "<option value='SUMMARY_WH'>WAREHOUSE</option>";
                $($tagId).append(option1);
                $($tagId).append(option2);

            } else {
                var option1 = "<option value='SUMMARY_WIP'>WIP</option>";
                $($tagId).append(option1);
            }
        };


    /* FUNCTION INITIALIZATION */
        function initialize_page(){
            feed_render_time_related_entry();
            //ajax_populateMaterialStock($materialStock);
            ajax_populateSloc($sloc);
            ajax_populateSloc($stockSloc);

            populate_cmbMaterial($materialStock);
        };
        function feed_render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var now = new Date();
            var year = now.getFullYear();
            var month = ("0" + (now.getMonth() + 1)).slice(-2); // Menambahkan 1 karena getMonth() mengembalikan bulan dari 0-11
            var formattedDate = `${year}-${month}-01`; // Membuat tanggal dengan tanggal 1 bulan tersebut

            $($endDate).val(currentDate);
            $($startDate).val(formattedDate);

            $($stockDateStart).val(formattedDate);
            $($stockDateEnd).val(currentDate);
        };
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            return hours + ":" + minutes;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

</script>
@endpush

@extends('layouts.app_user')
@section('title', 'TS Report')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row" style="padding-left:15px">
                            <h4>Summary of Daily Transaction</h4>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="name">Select Entry Date </label>
                                    <input type="date" name="entryDate" id="entryDate" style="width: 100%;" class="form-control col-sm-12" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- RM TRANSACTION -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h3><span class="badge bg-primary" style="color:white;">RM Transaction</span></h3>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableReport-rm" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Entry Date</th>
                                            <th>Prev Trace No</th>
                                            <th>Trace No</th>
                                            <th>Material</th>
                                            <th>Qty In (MT)</th>
                                            <th>SLoc</th>
                                            <th>Qty Out (MT)</th>
                                            <th>Qty Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- WIP TRANSACTION -->
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h3><span class="badge bg-primary" style="color:white;">WIP Transaction</span></h3>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableReport-wip" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Entry Date</th>
                                            <th>Prev Trace No</th>
                                            <th>Trace No</th>
                                            <th>Material</th>
                                            <th>WIP Out (MT)</th>
                                            <th>WIP Section</th>
                                            <th>WIP In (MT)</th>
                                            <th>WIP Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- TRANSFER TRANSACTION -->
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h3><span class="badge bg-primary" style="color:white;">TRANSFER Transaction</span></h3>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableReport-transfer" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Entry Date</th>
                                            <th>Prev Trace No</th>
                                            <th>Trace No</th>
                                            <th>Material</th>
                                            <th>Qty In (MT)</th>
                                            <th>SLOC</th>
                                            <th>Qty Out (MT)</th>
                                            <th>Qty Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- PACKAGING TRANSACTION -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h3><span class="badge bg-primary" style="color:white;">PACKAGING Transaction</span></h3>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableReport-pck" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Entry Date</th>
                                            <th>Prev Trace No</th>
                                            <th>Trace No</th>
                                            <th>PPH Batch No</th>
                                            <th>Material</th>
                                            <th>Qty In (MT)</th>
                                            <th>Qty Out (MT)</th>
                                            <th>Qty Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SHIPMENT TRANSACTION -->
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <h3><span class="badge bg-primary" style="color:white;">SHIPMENT Transaction</span></h3>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="tableReport-ship" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
                                            <th>Entry Date</th>
                                            <th>Prev Trace No</th>
                                            <th>Trace No</th>
                                            <th>SO No</th>
                                            <th>Material</th>
                                            <th>Qty In (MT)</th>
                                            <th>Qty Out (MT)</th>
                                            <th>Qty Supplier (MT)</th>
                                            <th>Supplier / Batch SAP / Qty (MT)</th>
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
        var index_url   = "{{ route('tsreport.index') }}";
        var post_url    = "{{ route('tsreport.store') }}";
        var show_url    = "{{ route('tsreport.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */

        const $entryDate        = '#entryDate';
        const $tableReport      = '#tableReport-wip';
        const $tableReport_rm   = '#tableReport-rm';
        const $tableReport_pck  = '#tableReport-pck';
        const $tableReport_ship = '#tableReport-ship';
        const $tableReport_trf  = '#tableReport-transfer';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            /* EVENT LISTENER ON CHANGE */
                $(document).on('change', $entryDate, function(){
                    ajax_populateTblTsReport($tableReport, $($entryDate).val());
                    ajax_populateTblTsReportRm($tableReport_rm, $($entryDate).val());
                    ajax_populateTblTsReportPck($tableReport_pck, $($entryDate).val());
                    ajax_populateTblTsReportShip($tableReport_ship, $($entryDate).val());
                    ajax_populateTblTsReportTrf($tableReport_trf, $($entryDate).val());
                });

            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_populateTblTsReportTrf($id, $dat_entryDate){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtTsReportTrf',
                        entryDate: $dat_entryDate
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
                },
                {
                    targets: [8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        const inQty = parseValue(rowData.in_qty);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        const outQty = parseValue(rowData.out_qty);

                        if (outQty === 0) {
                            if (inQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (inQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        } else if (outQty !== 0) {
                            if (outQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (outQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'from_trace_no', name: 'from_trace_no', className: 'text-center' },
                    { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center' },
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        }
        function ajax_populateTblTsReportShip($id, $dat_entryDate){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtTsReportShip',
                        entryDate: $dat_entryDate
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
                },
                {
                    targets: [8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        const inQty = parseValue(rowData.in_qty);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        const outQty = parseValue(rowData.out_qty);

                        if (outQty === 0) {
                            if (inQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (inQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        } else if (outQty !== 0) {
                            if (outQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (outQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'from_trace_no', name: 'from_trace_no', className: 'text-center' },
                    { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center' },
                    { data: 'so_no', name: 'so_no', className: 'text-center' },
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        }
        function ajax_populateTblTsReportPck($id, $dat_entryDate){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtTsReportPck',
                        entryDate: $dat_entryDate
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
                },
                {
                    targets: [8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        const inQty = parseValue(rowData.in_qty);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        const outQty = parseValue(rowData.out_qty);

                        if (outQty === 0) {
                            if (inQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (inQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        } else if (outQty !== 0) {
                            if (outQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (outQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'from_trace_no', name: 'from_trace_no', className: 'text-center' },
                    { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center' },
                    { data: 'batch_no', name: 'batch_no', className: 'text-center' },
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        }
        function ajax_populateTblTsReportRm($id, $dat_entryDate){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtTsReportRm',
                        entryDate: $dat_entryDate
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
                },
                {
                    targets: [8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        const inQty = parseValue(rowData.in_qty);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        const outQty = parseValue(rowData.out_qty);

                        if (outQty === 0) {
                            if (inQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (inQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        } else if (outQty !== 0) {
                            if (outQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (outQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'from_trace_no', name: 'from_trace_no', className: 'text-center' },
                    { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center' },
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        };
        function ajax_populateTblTsReport($id, $dat_entryDate){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtTsReport',
                        entryDate: $dat_entryDate
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
                },
                {
                    targets: [8], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        // Function to parse and remove commas
                        const parseValue = (value) => {
                            if (value === null || value === undefined) return 0; // Default to 0 if value is null or undefined
                            return parseFloat(value.toString().replace(/,/g, '')) || 0; // Default to 0 if parsing fails
                        };

                        const inQty = parseValue(rowData.in_qty);
                        const balanceSupplier = parseValue(rowData.balance_supplier);
                        const outQty = parseValue(rowData.out_qty);

                        if (outQty === 0) {
                            if (inQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (inQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        } else if (outQty !== 0) {
                            if (outQty === balanceSupplier) {
                                $(td).css('color', 'green');
                            } else if (outQty !== balanceSupplier) {
                                $(td).css('color', 'red');
                            }
                        }
                    }
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'from_trace_no', name: 'from_trace_no', className: 'text-center' },
                    { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center' },
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'section', name: 'section', className: 'text-center'},
                    { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                ]
            });
        };

    /* FUNCTION DYNAMICS */



    /* FUNCTION INITIALIZATION */



    /* FUNCTION AUTO-REFRESH */
        function initialize_page(){
            feed_render_time_related_entry();
            ajax_populateTblTsReport($tableReport, $($entryDate).val());
            ajax_populateTblTsReportRm($tableReport_rm, $($entryDate).val());
            ajax_populateTblTsReportPck($tableReport_pck, $($entryDate).val());
            ajax_populateTblTsReportShip($tableReport_ship, $($entryDate).val());
        };
        function feed_render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($entryDate).val(currentDate);
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

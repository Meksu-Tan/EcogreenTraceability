<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-cpko-balance">
    <div class="modal-dialog" role="document" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="balance-cpko-header"><span>CPKO BALANCE PER BATCHES</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped dataTable no-footer" id="table-cpko-balance" width="100%" role="grid" aria-describedby="table-1_info">
                                        <thead>
                                            <tr>
                                                <th>Entry Date</th>
                                                <th>Trace No</th>
                                                <th>Matl Doc</th>
                                                <th>Material</th>
                                                <th>Sloc</th>
                                                <th>Init Material (MT)</th>
                                                <th>Init Supplier (MT)</th>
                                                <th>RM Balance (MT)</th>
                                                <th>Supplier / Batch SAP / Init_Qty (MT) / Last_Qty (MT)</th>
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
    </div>
</div>
<?php $__env->startPush('js'); ?>
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "<?php echo e(route('wipentry.index')); ?>";
        var post_url    = "<?php echo e(route('wipentry.store')); ?>";
        var show_url    = "<?php echo e(route('wipentry.show', ':id')); ?>";

    /* VAR INDEX & PARAMETERIZATION */
        const $tbl_cpkoBalance           = '#table-cpko-balance';
        const $balance_cpkoHeader        = '#balance-cpko-header';

    /* FUNCTION DOCUMENT READY */
    $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_cpkoBalanceModal();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_dtCpkoBalance($id, $rundownId){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtBalance',
                        rundownId: $rundownId
                    }
                },
                order: [[ 0, 'desc']],
                responsive: true,
                searching: false,
                paging: false,
                info: false,
                columnDefs: [{
                    targets: [5,6], // index of 'balance_supplier' column
                    createdCell: function(td, cellData, rowData) {
                        if (rowData.init_qty === rowData.balance_supplier) {
                            $(td).css('color', 'green');
                        } else {
                            $(td).css('color', 'red');
                        }
                    }
                }],
                columns: [
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'trace_no', name: 'trace_no', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left'},
                    { data: 'sloc', name: 'sloc', className: 'text-center'},
                    { data: 'init_qty', name: 'init_qty', className: 'text-right'},
                    { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                    { data: 'qty', name: 'qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'}
                ]
            });
        };


    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_cpkoBalanceModal($material, $rundownId){
            $($balance_cpkoHeader).html($material + ' BALANCE PER BATCHES');
            ajax_dtCpkoBalance($tbl_cpkoBalance, $rundownId);
        };

    /* FUNCTION AUTO-REFRESH */


</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/user/trans_wip/modals/__balanceCpkoModal.blade.php ENDPATH**/ ?>
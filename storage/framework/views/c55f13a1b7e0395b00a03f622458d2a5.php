<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-cpko-feedlog">
    <div class="modal-dialog" role="document" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>RM FEED LOGS</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped dataTable no-footer" id="table-cpko-feedlog" width="100%" role="grid" aria-describedby="table-1_info">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>Feed Trace No</th>
                                                <th>Entry Date</th>
                                                <th>Matl Doc</th>
                                                <th>Material</th>
                                                <th>Last Feed (MT)</th>
                                                <th>Curr Feed (MT)</th>
                                                <th>Total (MT)</th>
                                                <th>RM Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
        const $tbl_cpkoFeedLog                   = '#table-cpko-feedlog';


    /* FUNCTION DOCUMENT READY */
    $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_cpkoFeedLogModal();

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_dtCpkoFeedLog($id, $feedId){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtFeed',
                        mode: 'LOG',
                        feedId: $feedId
                    }
                },
                order: [[ 0, 'desc']],
                responsive: true,
                columns: [
                    { data: 'action', name: 'action', className: 'text-center'},
                    { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center'},
                    { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                    { data: 'material_document', name: 'material_document', className: 'text-center'},
                    { data: 'material', name: 'material', className: 'text-left', width:'20%'},
                    { data: 'last_qtf', name: 'last_qtf', className: 'text-right'},
                    { data: 'curr_qtf', name: 'curr_qtf', className: 'text-right'},
                    { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                    { data: 'supplier', name: 'supplier', className: 'text-left', width:'25%'}
                ]
            });
        };


    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_cpkoFeedLogModal($feedId=null){
            ajax_dtCpkoFeedLog($tbl_cpkoFeedLog, $feedId);
        };

    /* FUNCTION AUTO-REFRESH */


</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/user/trans_wip/modals/__feedLogCpkoModal.blade.php ENDPATH**/ ?>
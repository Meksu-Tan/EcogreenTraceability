<?php if(empty($model->po_so)): ?>
    <button class="btn btn-warning btn-sm" style="color:black"
            id="poso-addDocNo"
            title="Add"
            data-idTraceHead="<?php echo e($model->id_trace_head); ?>">
        Add PO No
    </button>
<?php else: ?>
    <?php echo e($model->po_so); ?> &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="poso-editDocNo"
            title="Edit"
            data-idTraceHead="<?php echo e($model->id_trace_head); ?>"
            data-number="<?php echo e($model->po_so); ?>">
    </button>
<?php endif; ?>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/user/trans_rm/datatables/__actionPoSoRm.blade.php ENDPATH**/ ?>
<?php if(empty($model->material_document)): ?>
    <button class="btn btn-warning btn-sm" style="color:black"
            id="feed-addDocNo"
            title="Add"
            data-idTraceHead="<?php echo e($model->id_trace_head); ?>">
        Add Doc No
    </button>
<?php else: ?>
    <?php echo e($model->material_document); ?> &nbsp;&nbsp;
    <button class="btn btn-warning btn-sm" style="color:white"
            id="feed-editDocNo"
            title="Edit"
            data-idTraceHead="<?php echo e($model->id_trace_head); ?>"
            data-number="<?php echo e($model->material_document); ?>">
    </button>
<?php endif; ?>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/user/trans_wip/datatables/__actionMatlDocFeed.blade.php ENDPATH**/ ?>
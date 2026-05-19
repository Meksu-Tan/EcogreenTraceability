<?php if($model->traced == 'N/A'): ?>
    <?php if($model->status == '0'): ?>
        <a href="#"
            data-href="<?php echo e($activate_url); ?>"
            id="activate-rm-entry"
            class="btn btn-icon btn-info btn-sm"
            title="Activate"
            style="font-size: 9px;">
                <i class="	fas fa-undo"></i>
        </a>
    <?php else: ?>
        <a href="#"
            data-href="<?php echo e($destroy_url); ?>"
            id="destroy-rm-entry"
            class="btn btn-icon btn-danger btn-sm"
            title="De-Activate"
            style="font-size: 10px;">
                <i class="	fas fa-trash"></i>
        </a>

        <a href="#"
            data-href="<?php echo e($update_url); ?>"
            data-idHeader="<?php echo e($model->id_balance_head); ?>"
            data-idTank="<?php echo e($model->id_tank); ?>"
            data-idMaterial="<?php echo e($model->id_material); ?>"
            data-idRmNumber="<?php echo e($model->trace_no); ?>"
            data-entryDate="<?php echo e($model->entry_date); ?>"
            data-materialDoc="<?php echo e($model->material_document); ?>"
            data-status="<?php echo e($model->status); ?>"
            data-po="<?php echo e($model->po_so); ?>"
            id="update-rm-entry"
            class="btn btn-icon btn-warning btn-sm"
            title="Update"
            style="font-size: 10px;">
                <i class="fas fa-pencil-alt"></i>
        </a>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/user/trans_rm/datatables/__actionRmList.blade.php ENDPATH**/ ?>
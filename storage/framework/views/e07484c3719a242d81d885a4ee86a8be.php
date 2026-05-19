<li class="<?php echo e(route('rmentry.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('rmentry.index')); ?>" class="nav-link"><i class="fas fa-brush"></i><span>Raw Material</span></a>
</li>
<li class="<?php echo e(route('wipentry.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('wipentry.index')); ?>" class="nav-link"><i class="fas fa-burn"></i><span>WIP</span></a>
</li>
<li class="<?php echo e(route('blending.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('blending.index')); ?>" class="nav-link"><i class="fas fa-project-diagram"></i><span>Blending</span></a>
</li>
<li class="<?php echo e(route('transfer.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('transfer.index')); ?>" class="nav-link"><i class="fas fa-bezier-curve"></i><span>Transfer</span></a>
</li>
<li class="<?php echo e(route('packageentry.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('packageentry.index')); ?>" class="nav-link"><i class="fas fa-box"></i><span>Packaging</span></a>
</li>
<li class="<?php echo e(route('shipmententry.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('shipmententry.index')); ?>" class="nav-link"><i class="fas fa-ship"></i><span>Shipment</span></a>
</li>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/layouts/_menu-eudr-trans.blade.php ENDPATH**/ ?>
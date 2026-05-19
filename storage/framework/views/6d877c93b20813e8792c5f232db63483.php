<li class="<?php echo e(route('forward.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('forward.index')); ?>" class="nav-link"><i class="fas fa-angle-double-right"></i><span>Forward Trace</span></a>
</li>
<li class="<?php echo e(route('backward.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('backward.index')); ?>" class="nav-link"><i class="fas fa-angle-double-left"></i><span>Backward Trace</span></a>
</li>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/layouts/_menu-eudr-dashboard.blade.php ENDPATH**/ ?>
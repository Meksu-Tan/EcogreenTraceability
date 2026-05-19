<li class="<?php echo e(route('tsreport.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('tsreport.index')); ?>" class="nav-link"><i class="fas fa-clipboard"></i><span>TS Report</span></a>
</li>
<li class="<?php echo e(route('stock.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('stock.index')); ?>" class="nav-link"><i class="fas fa-cubes"></i><span>Stock On-Hand</span></a>
</li>
<li class="<?php echo e(route('rmreport.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('rmreport.index')); ?>" class="nav-link"><i class="fas fa-cubes"></i><span>RM to PRD</span></a>
</li>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/layouts/_menu-eudr-record.blade.php ENDPATH**/ ?>
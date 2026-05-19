<li class="<?php echo e(route('adjustment.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('adjustment.index')); ?>" class="nav-link"><i class="fab fa-codiepie"></i><span>Adjustment</span></a>
</li>
<li class="<?php echo e(route('materialsetup.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('materialsetup.index')); ?>" class="nav-link"><i class="fab fa-asymmetrik"></i><span>Material</span></a>
</li>
<li class="<?php echo e(route('suppliersetup.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('suppliersetup.index')); ?>" class="nav-link"><i class="fas fa-diagnoses"></i><span>Supplier</span></a>
</li>
<li class="<?php echo e(route('storagesetup.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('storagesetup.index')); ?>" class="nav-link"><i class="	fas fa-database"></i><span>Storage</span></a>
</li>
<li class="<?php echo e(route('qtfsetup.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('qtfsetup.index')); ?>" class="nav-link"><i class="fab fa-slack"></i><span>Quantifier</span></a>
</li>
<li class="<?php echo e(route('plantsetup.index') == request()->url() ? 'active' : ''); ?>">
    <a href="<?php echo e(route('plantsetup.index')); ?>" class="nav-link"><i class="fas fa-balance-scale"></i><span>Plant</span></a>
</li>


<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/layouts/_menu-eudr-setup.blade.php ENDPATH**/ ?>
<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <div style="padding-top:10px">
            <img src="<?php echo e(asset('images/Logo EOB with name.jpg')); ?>" class="rounded mx-auto d-block"  height="50" width="170">
        </div>
        <a href="">EO-TRACE</a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="">TS</a>
    </div>
    <ul class="sidebar-menu">

        <?php if (app('laratrust')->hasRole('viewer|staff|senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')) : ?>
            <li class="menu-header">Dashboard</li>
            <?php echo $__env->make('layouts._menu-eudr-dashboard', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; // app('laratrust')->hasRole ?>

        <?php if (app('laratrust')->hasRole('staff|senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')) : ?>
            <li class="menu-header">TS Transaction</li>
            <?php echo $__env->make('layouts._menu-eudr-trans', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; // app('laratrust')->hasRole ?>

        <?php if (app('laratrust')->hasRole('viewer|staff|senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')) : ?>
            <li class="menu-header">TS Inquiry</li>
            <?php echo $__env->make('layouts._menu-eudr-record', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; // app('laratrust')->hasRole ?>

        <?php if (app('laratrust')->hasRole('senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')) : ?>
            <li class="menu-header">TS Setup</li>
            <?php echo $__env->make('layouts._menu-eudr-setup', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; // app('laratrust')->hasRole ?>

        <?php if (app('laratrust')->hasRole('manager|admin|super-admin')) : ?>
            <li class="menu-header">Admin Setup</li>
            <?php echo $__env->make('layouts._menu-admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php endif; // app('laratrust')->hasRole ?>

      </ul>
    </li>
  </ul>
</div>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/layouts/_sidebar-left.blade.php ENDPATH**/ ?>
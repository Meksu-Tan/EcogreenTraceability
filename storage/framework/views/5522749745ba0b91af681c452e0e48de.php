<nav class="navbar navbar-expand-lg main-navbar">
  <form class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
      <!-- <li><a href="#" data-toggle="search" class="nav-link nav-link-lg d-sm-none"><i class="fas fa-search"></i></a></li> -->
    </ul>
  </form>
  <ul class="navbar-nav navbar-right">

    <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
      <div class="d-sm-none d-lg-inline-block">Hi, <?php echo e(Auth::user()->name); ?></div></a>
      <div class="dropdown-menu dropdown-menu-right">
        <div class="dropdown-title">Account</div>
        <a href="<?php echo e(route('guidance.index')); ?>" class="dropdown-item has-icon">
          <i class="far fa-file-code"></i> Guidance
        </a>
        <a href="<?php echo e(route('logout')); ?>"
          onclick="event.preventDefault();
          document.getElementById('logout-form').submit();" class="dropdown-item has-icon text-danger">
          <i class="fas fa-sign-out-alt"></i> <?php echo e(__('Logout')); ?>

        </a>

      <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none">
        <?php echo csrf_field(); ?>
      </form>
      </div>
    </li>
  </ul>
</nav>
<?php /**PATH C:\XAMPP\htdocs\EODS\Master\resources\views/layouts/navbar_user.blade.php ENDPATH**/ ?>
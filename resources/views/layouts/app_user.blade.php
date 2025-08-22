<!DOCTYPE html>
<html lang="en">
<head>
    @include('layouts.head')
</head>
<body>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <div class="navbar-bg"></div>
       @include('layouts.navbar_user')
       @include('layouts._sidebar-left')

      <!-- Main Content -->
      <div class="main-content">
           @include('layouts.section_content')
      </div>
      @include('layouts.footer')
    </div>
  </div>
  <!-- General JS Scripts -->
  @include('layouts.script')
</body>
</html>

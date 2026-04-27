<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <div style="padding-top:10px">
            <img src="{{ asset('images/Logo EOB with name.jpg') }}" class="rounded mx-auto d-block"  height="50" width="170">
        </div>
        <a href="">EO-TRACE</a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="">TS</a>
    </div>
    <ul class="sidebar-menu">

        @role('viewer|staff|senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')
            <li class="menu-header">Dashboard</li>
            @include('layouts._menu-eudr-dashboard')
        @endrole

        @role('staff|senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')
            <li class="menu-header">TS Transaction</li>
            @include('layouts._menu-eudr-trans')
        @endrole

        @role('viewer|staff|senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')
            <li class="menu-header">TS Inquiry</li>
            @include('layouts._menu-eudr-record')
        @endrole

        @role('senior-staff|supervisor|senior-supervisor|superintendent|manager|admin|super-admin')
            <li class="menu-header">TS Setup</li>
            @include('layouts._menu-eudr-setup')
        @endrole

        @role('manager|admin|super-admin')
            <li class="menu-header">Admin Setup</li>
            @include('layouts._menu-admin')
        @endrole

      </ul>
    </li>
  </ul>
</div>

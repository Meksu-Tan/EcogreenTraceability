<li class="{{ route('forward.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('forward.index') }}" class="nav-link"><i class="fas fa-angle-double-right"></i><span>Forward Trace</span></a>
</li>
<li class="{{ route('backward.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('backward.index') }}" class="nav-link"><i class="fas fa-angle-double-left"></i><span>Backward Trace</span></a>
</li>

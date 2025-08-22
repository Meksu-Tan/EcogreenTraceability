<li class="{{ route('tsreport.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('tsreport.index') }}" class="nav-link"><i class="fas fa-clipboard"></i><span>TS Report</span></a>
</li>
<li class="{{ route('stock.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('stock.index') }}" class="nav-link"><i class="fas fa-cubes"></i><span>Stock On-Hand</span></a>
</li>
<li class="{{ route('rmreport.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('rmreport.index') }}" class="nav-link"><i class="fas fa-cubes"></i><span>RM to PRD</span></a>
</li>

<li class="{{ route('rmentry.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('rmentry.index') }}" class="nav-link"><i class="fas fa-brush"></i><span>Raw Material</span></a>
</li>
<li class="{{ route('wipentry.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('wipentry.index') }}" class="nav-link"><i class="fas fa-burn"></i><span>WIP</span></a>
</li>
<li class="{{ route('blending.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('blending.index') }}" class="nav-link"><i class="fas fa-project-diagram"></i><span>Blending</span></a>
</li>
<li class="{{ route('transfer.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('transfer.index') }}" class="nav-link"><i class="fas fa-bezier-curve"></i><span>Transfer</span></a>
</li>
<li class="{{ route('packageentry.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('packageentry.index') }}" class="nav-link"><i class="fas fa-box"></i><span>Packaging</span></a>
</li>
<li class="{{ route('shipmententry.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('shipmententry.index') }}" class="nav-link"><i class="fas fa-ship"></i><span>Shipment</span></a>
</li>

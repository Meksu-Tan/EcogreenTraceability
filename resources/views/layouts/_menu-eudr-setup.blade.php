<li class="{{ route('adjustment.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('adjustment.index') }}" class="nav-link"><i class="fab fa-codiepie"></i><span>Adjustment</span></a>
</li>
<li class="{{ route('materialsetup.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('materialsetup.index') }}" class="nav-link"><i class="fab fa-asymmetrik"></i><span>Material</span></a>
</li>
<li class="{{ route('suppliersetup.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('suppliersetup.index') }}" class="nav-link"><i class="fas fa-diagnoses"></i><span>Supplier</span></a>
</li>
<li class="{{ route('storagesetup.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('storagesetup.index') }}" class="nav-link"><i class="	fas fa-database"></i><span>Storage</span></a>
</li>
<li class="{{ route('qtfsetup.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('qtfsetup.index') }}" class="nav-link"><i class="fab fa-slack"></i><span>Quantifier</span></a>
</li>
<li class="{{ route('plantsetup.index') == request()->url() ? 'active' : '' }}">
    <a href="{{ route('plantsetup.index') }}" class="nav-link"><i class="fas fa-balance-scale"></i><span>Plant</span></a>
</li>



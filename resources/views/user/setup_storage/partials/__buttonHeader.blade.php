<div class="row">
    <div style="padding-left:5px">
        <button type="button" id="storage-tank" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> Setup Storage </button>
    </div>
    <div style="padding-left:5px">
        <button type="button" id="storage-warehouse" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> Setup Warehouse </button>
    </div>
</div>

@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('storagesetup.index') }}";
        var post_url    = "{{ route('storagesetup.store') }}";
        var show_url    = "{{ route('storagesetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $btn_storageTank           = '#storage-tank';
        const $btn_storageWarehouse      = '#storage-warehouse';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SINGLE-CLICK */
                $(document).on('click', $btn_storageTank, function(){
                    window.location.href = "{{ route('storagesetup.edit', 'storage_tank') }}";
                });
                $(document).on('click', $btn_storageWarehouse, function(){
                    window.location.href = "{{ route('storagesetup.edit', 'storage_warehouse') }}";
                });
        });

</script>
@endpush

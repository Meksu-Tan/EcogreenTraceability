<div class="row">
    <div style="padding-left:5px">
        <button type="button" id="pck-entry" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> Packaging Entry </button>
    </div>
    <div style="padding-left:5px">
        <button type="button" id="pck-warehouse" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> Warehouse Stock </button>
    </div>
</div>

@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('packageentry.index') }}";
        var post_url    = "{{ route('packageentry.store') }}";
        var show_url    = "{{ route('packageentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $btn_pckEntry           = '#pck-entry';
        const $btn_pckWarehouse       = '#pck-warehouse';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SINGLE-CLICK */
                $(document).on('click', $btn_pckEntry, function(){
                    window.location.href = "{{ route('packageentry.edit', 'pck_entry') }}";
                });
                $(document).on('click', $btn_pckWarehouse, function(){
                    window.location.href = "{{ route('packageentry.edit', 'pck_warehouse') }}";
                });
            });

</script>
@endpush

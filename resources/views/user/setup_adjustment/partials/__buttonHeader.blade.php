<div class="row">
    <div style="padding-left:5px">
        <button type="button" id="adjustment-wip" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> WIP Adjustment </button>
    </div>
    <div style="padding-left:5px">
        <button type="button" id="adjustment-warehouse" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> WAREHOUSE Adjustment </button>
    </div>
</div>

@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('adjustment.index') }}";
        var post_url    = "{{ route('adjustment.store') }}";
        var show_url    = "{{ route('adjustment.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $btn_adjtWip           = '#adjustment-wip';
        const $btn_adjtWarehouse     = '#adjustment-warehouse';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SINGLE-CLICK */
                $(document).on('click', $btn_adjtWip, function(){
                    window.location.href = "{{ route('adjustment.edit', 'adjustment_wip') }}";
                });
                $(document).on('click', $btn_adjtWarehouse, function(){
                    window.location.href = "{{ route('adjustment.edit', 'adjustment_warehouse') }}";
                });
        });

</script>
@endpush

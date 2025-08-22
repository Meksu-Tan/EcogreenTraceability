<div class="row">
    <div style="padding-left:5px">
        <button type="button" id="matl-wip" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> Setup WIP/PRD Material </button>
    </div>
    <div style="padding-left:5px">
        <button type="button" id="matl-packaging" class="btn btn-secondary" style="color:black; margin-top:5px"><i class="fas fa-bars" aria-hidden="true"></i> Setup PACKAGING Product </button>
    </div>
</div>

@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('materialsetup.index') }}";
        var post_url    = "{{ route('materialsetup.store') }}";
        var show_url    = "{{ route('materialsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $btn_matlWip           = '#matl-wip';
        const $btn_matlPackaging     = '#matl-packaging';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SINGLE-CLICK */
                $(document).on('click', $btn_matlWip, function(){
                    window.location.href = "{{ route('materialsetup.edit', 'matl_wip') }}";
                });
                $(document).on('click', $btn_matlPackaging, function(){
                    window.location.href = "{{ route('materialsetup.edit', 'matl_packaging') }}";
                });
        });

</script>
@endpush

<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-plant">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-plant-header">Setup Plant</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-plant" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-plant-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-plant-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-plant-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">ID Tank *</label>
                                        <input type="text" name="id_tank" id="form-plant-idTank" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Plant Type</label>
                                        <select name="type" id="form-plant-type" style="width: 100%;" class="form-control">
                                            <option value="">No Type</option>
                                            <option value="EOB-1">EOB-1</option>
                                            <option value="EOB-2">EOB-2</option>
                                            <option value="EOB-3">EOB-3</option>
                                            <option value="EOMB">EOMB</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Plant Code</label>
                                        <input type="text" name="code" id="form-plant-code" style="width: 100%; text-transform: uppercase;" class="form-control col-sm-12" autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Plant Description *</label>
                                        <input type="text" name="description" id="form-plant-description" style="width: 100%; text-transform: uppercase;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-plant">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('js')
<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('plantsetup.index') }}";
        var post_url    = "{{ route('plantsetup.store') }}";
        var show_url    = "{{ route('plantsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_plant             = '#form-plant';

        const $txt_plant_flag         = '#form-plant-flag';
        const $txt_plant_mode         = '#form-plant-mode';
        const $txt_plant_id           = '#form-plant-id';
        const $txt_plant_code         = '#form-plant-code';
        const $txt_plant_description  = '#form-plant-description';
        const $cmb_plant_type         = '#form-plant-type';
        const $txt_plant_idTank        = '#form-plant-idTank';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_plant_modal();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_plant).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_plant_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' PLANT?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, ' + $mode + ' it',
                        cancelButtonText: 'No, cancel',
                    }).then((result) => {
                        if (result.value) {
                            $.ajax({
                                url: post_url,
                                method: "POST",
                                data: formData,
                                contentType: false,
                                cache: false,
                                processData: false,
                                dataType: "JSON",
                                success: function(data) {
                                    if (data.status == 1) {
                                        Swal.fire({
                                            position: 'top-end',
                                            icon: 'success',
                                            title: data.message,
                                            showConfirmButton: false,
                                            timer: 500
                                        });

                                        $($dt_plant).DataTable().ajax.reload();
                                        $($modal_plant).modal('hide');

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });
                });
        });

    /* FUNCTION INITIALIZATION */
        function initialize_plant_modal($flag=null, $mode=null, $id=null, $code=null, 
                                        $description=null, $idTank=null, $type=null) {

            $($txt_plant_flag).val($flag);
            $($txt_plant_mode).val($mode);
            $($txt_plant_id).val($id);
            $($txt_plant_code).val($code);
            $($txt_plant_description).val($description);
            $($txt_plant_idTank).val($idTank);
            $($cmb_plant_type).val($type);

        };

</script>
@endpush

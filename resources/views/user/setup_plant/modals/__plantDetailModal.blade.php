<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-plantDetail">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-plantDetail-header">Setup Plant Details</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-plantDetail" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-plantDetail-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-plantDetail-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id_plant" id="form-plantDetail-idPlant" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-plantDetail-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Plant</label>
                                        <input type="text" name="plant" id="form-plantDetail-plant" style="width: 100%; text-transform: uppercase;" class="form-control" readonly autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Plant Number</label>
                                        <input type="text" name="tf_number" id="form-plantDetail-tfNumber" style="width: 100%; text-transform: uppercase;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-plantDetail">Save</button>
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
        const $form_plantDetail             = '#form-plantDetail';

        const $txt_plantDetail_flag         = '#form-plantDetail-flag';
        const $txt_plantDetail_mode         = '#form-plantDetail-mode';
        const $txt_plantDetail_id           = '#form-plantDetail-id';
        const $txt_plantDetail_idPlant       = '#form-plantDetail-idPlant';
        const $txt_plantDetail_plant      = '#form-plantDetail-plant';

        const $txt_plantDetail_tfNumber     = '#form-plantDetail-tfNumber';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_plantDetail_modal();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_plantDetail).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_plantDetail_mode).val();

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

                                        $($dt_plantDetail).DataTable().ajax.reload();
                                        $($modal_plantDetail).modal('hide');

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
        function initialize_plantDetail_modal($flag=null, $mode=null, $idPlant=null, $plant=null, $id=null,
                                                $tfNumber=null){

            $($txt_plantDetail_flag).val($flag);
            $($txt_plantDetail_mode).val($mode);
            $($txt_plantDetail_id).val($id);
            $($txt_plantDetail_plant).val($plant);
            $($txt_plantDetail_tfNumber).val($tfNumber);
            $($txt_plantDetail_idPlant).val($idPlant);
        };

</script>
@endpush
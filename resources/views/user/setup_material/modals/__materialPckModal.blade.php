<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-matlPck">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-matlPck-title">Setup Packaging Product</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-matlPck" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-matlPck-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-matlPck-id" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-matlPck-mode" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Code</label>
                                        <input type="text" name="code" id="form-matlPck-code" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Code (Non-EUDR)</label>
                                        <input type="number" name="code_noneudr" id="form-matlPck-codeMaterialNonEudr" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Material Description</label>
                                        <input type="text" name="description" id="form-matlPck-description" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Source Product</label>
                                        <select name="source" id="form-matlPck-source" style="width: 100%;" class="form-control" required>
                                            <option value="">- Select Source Product -</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-matlPck">Save</button>
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
        var index_url   = "{{ route('materialsetup.index') }}";
        var post_url    = "{{ route('materialsetup.store') }}";
        var show_url    = "{{ route('materialsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_matlPck            = '#form-matlPck';

        const $txt_matlPck_flag        = '#form-matlPck-flag';
        const $txt_matlPck_mode        = '#form-matlPck-mode';
        const $txt_matlPck_id          = '#form-matlPck-id';

        const $txt_matlPck_code        = '#form-matlPck-code';
        const $txt_matlPck_codeNonEudr = '#form-matlPck-codeMaterialNonEudr';
        const $txt_matlPck_description = '#form-matlPck-description';
        const $cmb_matlPck_source      = '#form-matlPck-source';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_matlPck).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_matlPck_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' PACKAGING PRODUCT?',
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

                                        $($dt_matlPck).DataTable().ajax.reload();
                                        $($modal_matlPck).modal('hide');

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });
                });
        });

    /* FUNCTION AJAX */
        function ajax_populateSourceProduct(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveSourceProduct',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_material;
                            var populate_2 = response['data'][i].material;

                            if (selectedValue) {
                                if (populate_1 == selectedValue) {
                                    var option = "<option value='"+populate_1+"' selected='"+selectedValue+"'>"+populate_2+"</option>";
                                } else {
                                    var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                                }
                            } else {
                                var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                            }
                            $(id).append(option);
                        }
                    }
                }
            });
        }

    /* FUNCTION INITIALIZATION */
        function initialize_matlPck_modal($id, $code, $codeNonEudr, $description, $source, $mode){
            $($txt_matlPck_flag).val('post_storeMatlPck');
            $($txt_matlPck_mode).val($mode);
            $($txt_matlPck_id).val($id);
            $($txt_matlPck_code).val($code);
            $($txt_matlPck_codeNonEudr).val($codeNonEudr);
            $($txt_matlPck_description).val($description);

            // $($txt_matlPck_code).val('');
            // $($txt_matlPck_codeNonEudr).val('');
            // $($txt_matlPck_description).val('');
            // $($cmb_matlPck_source).val('');

            ajax_populateSourceProduct($cmb_matlPck_source, $source);
        };

</script>
@endpush

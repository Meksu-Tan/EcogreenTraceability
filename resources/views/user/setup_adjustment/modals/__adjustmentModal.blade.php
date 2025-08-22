<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-adjustment">
    <div class="modal-dialog" role="document" style="max-width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-adjustment-title">ADJUSTMENT DATA (ONLY FOR STOCK QTY NOT EQUAL 0)</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-adjustment" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-adjustment-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-adjustment-id" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-adjustment-mode" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Adjusment Entry Date</label>
                                                <input type="date" name="entryDate" id="form-adjustment-entryDate" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Material</label>
                                                <select name="id_material" id="form-adjustment-material" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Material -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="idTank" id="form-adjustment-tank" style="width: 100%;" class="form-control">
                                                    <option value="">- No Sloc -</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">New Balance Qty (MT)</label>
                                                <input type="number" name="qty" id="form-adjustment-qty" style="width: 100%;" class="form-control col-sm-12" step="any" required autocomplete="off">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-feed">Save</button>
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
        var index_url   = "{{ route('adjustment.index') }}";
        var post_url    = "{{ route('adjustment.store') }}";
        var show_url    = "{{ route('adjustment.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_adjustment_title        = '#modal-adjustment-title';

        const $form_adjustment              = '#form-adjustment';
        const $form_adjustment_flag         = '#form-adjustment-flag';
        const $form_adjustment_id           = '#form-adjustment-id';
        const $form_adjustment_mode         = '#form-adjustment-mode';

        const $form_adjustment_batchNo      = '#form-adjustment-batchNo';
        const $form_adjustment_qty          = '#form-adjustment-qty';
        const $form_adjustment_entryDate    = '#form-adjustment-entryDate';
        const $form_adjustment_material     = '#form-adjustment-material';
        const $form_adjustment_tank         = '#form-adjustment-tank';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_adjustment();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_adjustment).unbind().on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($form_adjustment_mode).val();
                    var $title = $($form_adjustment_title).html();
                    console.log($($form_adjustment_material).val());
                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' ' + $title + ' ?',
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

                                        $($modal_adjustment).modal('hide');
                                        initialize_page();

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */



        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateMaterial(id, $feedID, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveMaterial',
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
        };
        function ajax_populateTank(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_tank;
                            var populate_2 = response['data'][i].tank;

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

    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_adjustment($mode=null, $flag=null, $id=null, $idTank=null){
            $($form_adjustment_flag).val($flag);
            $($form_adjustment_id).val($id);
            $($form_adjustment_mode).val($mode);

            $($form_adjustment_qty).val('0');
            $($form_adjustment_entryDate).val();

            adjustment_render_time_related_entry();

            if ($flag == 'post_storeAdjustment'){
                ajax_populateMaterial($form_adjustment_material);
                ajax_populateTank($form_adjustment_tank, $idTank);
            } else if ($flag == 'post_storeAdjustmentWhx'){
                ajax_populateMaterialWhx($form_adjustment_material);
                ajax_populateWhx($form_adjustment_tank, $idTank);
            }

        };

        function adjustment_render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($form_adjustment_entryDate).val(currentDate);
        };
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            return hours + ":" + minutes;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

    /* FUNCTION AUTO-REFRESH */


</script>
@endpush

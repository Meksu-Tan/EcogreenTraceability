<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-rm-trf-entryMaterial">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Material Transfer</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-rmentryMaterial" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-rmentryMaterial-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-rmentryMaterial-idHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTail" id="form-rmentryMaterial-idTail" class="form-control text-uppercase" required>
                                        <input type="hidden" name="mode" id="form-rmentryMaterial-mode" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entryNo" id="form-rmentryMaterial-entryNo" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTankStorage" id="form-rmentryMaterial-idTankStorage" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idTankFeed" id="form-rmentryMaterial-idTankFeed" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entryDate" id="form-rmentryMaterial-entryDate" class="form-control text-uppercase" required>
                                        <input type="hidden" name="materialDoc" id="form-rmentryMaterial-materialDoc" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Raw Material</label>
                                        <select name="idMaterial" id="form-rmentryMaterial-idMaterial" style="width: 100%;" class="form-control" required>
                                            <option value=''>- Select Material -</option>
                                        </select>
                                        <label for="name" id="form-rmentryMaterial-stock">Stock : N/A</label>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Entry Qty (MT)</label>
                                        <input type="number" name="qty" id="form-rmentryMaterial-qty" class="form-control text-uppercase" step="any" required>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="insert-rmentryMaterial">Insert Material</button>
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
        var index_url   = "{{ route('rmentry.index') }}";
        var post_url    = "{{ route('rmentry.store') }}";
        var show_url    = "{{ route('rmentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_rmentryMaterial                 = '#form-rmentryMaterial';

        const $txt_rmentryMaterial_flag             = '#form-rmentryMaterial-flag';
        const $txt_rmentryMaterial_mode             = '#form-rmentryMaterial-mode';
        const $txt_rmentryMaterial_idHead           = '#form-rmentryMaterial-idHead';
        const $txt_rmentryMaterial_idTail           = '#form-rmentryMaterial-idTail';
        const $txt_rmentryMaterial_entryNo          = '#form-rmentryMaterial-entryNo';
        const $txt_rmentryMaterial_idTankFeed       = '#form-rmentryMaterial-idTankFeed';
        const $txt_rmentryMaterial_idTankStorage    = '#form-rmentryMaterial-idTankStorage';
        const $txt_rmentryMaterial_entryDate        = '#form-rmentryMaterial-entryDate';
        const $txt_rmentryMaterial_materialDoc      = '#form-rmentryMaterial-materialDoc';
        const $txt_rmentryMaterial_idMaterial       = '#form-rmentryMaterial-idMaterial';
        const $txt_rmentryMaterial_qty              = '#form-rmentryMaterial-qty';
        const $lbl_rmentryMaterial_stock            = '#form-rmentryMaterial-stock';

        const $btn_rmentryMaterial_insert           = '#insert-rmentryMaterial';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_rmentryMaterial).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_rmentryMaterial_mode).val();

                    var $stockDat = $($lbl_rmentryMaterial_stock).html();
                    var $stock = $stockDat.split(":")[1].trim();

                    if (parseInt($stock) < parseInt($($txt_rmentryMaterial_qty).val())){
                        Swal.fire({
                            position: 'top-end',
                            icon: 'error',
                            title: 'Qty > Stock !',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' MATERIAL ?',
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

                                        $($modal_rmEntryTrf_addMaterial).modal('hide');
                                        initialize_modalRmTrfEntry(data.mode, data.entryNo, data.idHead, data.entryDate,
                                                                   data.idTankStorage, data.idTankFeed, data.materialDoc);
                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });
            /* LISTENER ON CLICK/ CHANGE FUNCTION */
                $(document).on('change', $txt_rmentryMaterial_idMaterial, function(e){
                    e.preventDefault();
                    ajax_getStockMaterial($lbl_rmentryMaterial_stock, $($txt_rmentryMaterial_idMaterial).val(), $($txt_rmentryMaterial_idTankStorage).val());
                });

        });

    /* FUNCTION AJAX */
        function ajax_cmbMaterial(id, selectedValue=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
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

                        ajax_getStockMaterial($lbl_rmentryMaterial_stock, $($txt_rmentryMaterial_idMaterial).val(), $($txt_rmentryMaterial_idTankStorage).val());
                    }
                }
            });
        };
        function ajax_getStockMaterial($id, $idMaterial, $idTank){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_totalStockMaterial',
                    idMaterial: $idMaterial,
                    idTank: $idTank
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).html("Stock (MT): " + response['data'][0].total);
                    }
                }
            });
        }

    /* FUNCTION INIT */
        function initialize_modalRmEntryMaterial($mode, $idHead=null, $idTail=null, $entryNo=null, $qty=null,
                                                 $idTankFeed=null, $idTankStorage=null, $entryDate=null,
                                                 $materialDoc=null, $idMaterial=null, $stock=null){

            $($txt_rmentryMaterial_flag).val('post_rmEntryMaterial');
            $($txt_rmentryMaterial_mode).val($mode);
            $($txt_rmentryMaterial_idHead).val($idHead);
            $($txt_rmentryMaterial_idTail).val($idTail);
            $($txt_rmentryMaterial_entryNo).val($entryNo);
            $($txt_rmentryMaterial_qty).val($qty);
            $($txt_rmentryMaterial_entryDate).val($entryDate);
            $($txt_rmentryMaterial_idTankFeed).val($idTankFeed);
            $($txt_rmentryMaterial_idTankStorage).val($idTankStorage);
            $($txt_rmentryMaterial_materialDoc).val($materialDoc);
            $($lbl_rmentryMaterial_stock).val($stock);

            if ($mode == 'ADD'){
                ajax_cmbMaterial($txt_rmentryMaterial_idMaterial);
            } else {
                ajax_cmbMaterial($txt_rmentryMaterial_idMaterial, $idMaterial);
            }
        };

    /* FUNCTION AUTO-REFRESH */
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            seconds = format_two_digits(d.getSeconds());

            return hours + ":" + minutes + ":" + seconds;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

</script>
@endpush

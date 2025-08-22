<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-blendingEntry-material">
    <div class="modal-dialog" role="document" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Blend Entry Source Material</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-blendingEntryMaterial" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-blendingEntryMaterial-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-blendingEntryMaterial-idHead" class="form-control text-uppercase" required>
                                        <input type="hidden" name="mode" id="form-blendingEntryMaterial-mode" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entryNo" id="form-blendingEntryMaterial-entryNo" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idMaterial" id="form-blendingEntryMaterial-idMaterial" class="form-control text-uppercase" required>
                                        <input type="hidden" name="entryDate" id="form-blendingEntryMaterial-entryDate" class="form-control text-uppercase" required>
                                        <input type="hidden" name="materialDoc" id="form-blendingEntryMaterial-materialDoc" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Blending Material</label>
                                        <select name="idMaterialSource" id="form-blendingEntryMaterial-idMaterialSource" style="width: 100%;" class="form-control" required>
                                            <option value=''>- Select Material -</option>
                                        </select>
                                        <label for="name" id="form-blendingEntryMaterial-stock">Stock : N/A</label>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Entry Qty (MT)</label>
                                        <input type="number" name="qty" id="form-blendingEntryMaterial-qty" class="form-control text-uppercase" step="any" required>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="insert-blendingEntryMaterial">Insert Material</button>
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
        var index_url   = "{{ route('blending.index') }}";
        var post_url    = "{{ route('blending.store') }}";
        var show_url    = "{{ route('blending.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_blendingEntryMaterial                 = '#form-blendingEntryMaterial';

        const $txt_blendingEntryMaterial_flag             = '#form-blendingEntryMaterial-flag';
        const $txt_blendingEntryMaterial_mode             = '#form-blendingEntryMaterial-mode';
        const $txt_blendingEntryMaterial_idHead           = '#form-blendingEntryMaterial-idHead';
        const $txt_blendingEntryMaterial_entryNo          = '#form-blendingEntryMaterial-entryNo';
        const $txt_blendingEntryMaterial_idMaterial       = '#form-blendingEntryMaterial-idMaterial';
        const $txt_blendingEntryMaterial_entryDate        = '#form-blendingEntryMaterial-entryDate';
        const $txt_blendingEntryMaterial_materialDoc      = '#form-blendingEntryMaterial-materialDoc';
        const $txt_blendingEntryMaterial_idMaterialSource = '#form-blendingEntryMaterial-idMaterialSource';
        const $txt_blendingEntryMaterial_qty              = '#form-blendingEntryMaterial-qty';
        const $lbl_blendingEntryMaterial_stock            = '#form-blendingEntryMaterial-stock';

        const $btn_blendingEntryMaterial_insert           = '#insert-blendingEntryMaterial';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_blendingEntryMaterial).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_blendingEntryMaterial_mode).val();

                    var $stockDat = $($lbl_blendingEntryMaterial_stock).html();
                    var $stock = $stockDat.split(":")[1].trim();

                    if (parseInt($stock) < parseInt($($txt_blendingEntryMaterial_qty).val())){
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

                                        $($modal_blendEntry_material).modal('hide');
                                        initializeBlendEntry(data.mode, data.idHead, data.idMaterial, data.materialDoc,
                                                             data.entryDate, data.entryNo);
                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });

            /* LISTENER ON CLICK/ CHANGE FUNCTION */
                $(document).on('change', $txt_blendingEntryMaterial_idMaterialSource, function(e){
                    e.preventDefault();
                    ajax_getStockMaterial($lbl_blendingEntryMaterial_stock, $($txt_blendingEntryMaterial_idMaterialSource).val());
                });
        });

    /* FUNCTION AJAX */
        function ajax_getStockMaterial($id, $idMaterial){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_totalStockMaterial',
                    idMaterial: $idMaterial
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
        function initializeBlendEntryMaterial($mode, $idHead=null, $idMaterial=null, $materialDoc=null,
                                              $entryDate=null, $entryNo=null, $qty=null, $idMaterialSource=null){

            $($txt_blendingEntryMaterial_flag).val('post_blendingEntryMaterial');
            $($txt_blendingEntryMaterial_mode).val($mode);
            $($txt_blendingEntryMaterial_idHead).val($idHead);
            $($txt_blendingEntryMaterial_idMaterial).val($idMaterial);
            $($txt_blendingEntryMaterial_materialDoc).val($materialDoc);
            $($txt_blendingEntryMaterial_entryDate).val($entryDate);
            $($txt_blendingEntryMaterial_entryNo).val($entryNo);

            $($lbl_blendingEntryMaterial_stock).html('Stock : N/A');
            $($txt_blendingEntryMaterial_qty).val($qty);

            if ($mode == 'ADD'){
                ajax_cmbMaterial($txt_blendingEntryMaterial_idMaterialSource);
            } else {
                ajax_cmbMaterial($txt_blendingEntryMaterial_idMaterialSource, $idMaterialSource);
            };
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

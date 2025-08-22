<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-shipEntry">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-shipEntry-title">SHIPMENT ENTRY</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-shipEntry" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-shipEntry-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-shipEntry-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="balance" id="form-shipEntry-balance" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-shipEntry-mode" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Date</label>
                                                <input type="date" name="entryDate" id="form-shipEntry-date" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Shipment Product</label>
                                                <select name="fgProduct" id="form-shipEntry-fgProduct" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Product -</option>
                                                </select>
                                                <label for="name" id="form-shipEntry-bom">Product : N/A</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Batch Product</label>
                                                <select name="batch_no" id="form-shipEntry-batch" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Batch -</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Sales Order (SO) No</label>
                                                <input type="text" name="soNo" id="form-shipEntry-soNo" style="width: 100%;"
                                                            class="form-control col-sm-12" required autocomplete="off"
                                                            oninput="this.value = this.value.toUpperCase();">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Qty (MT)</label>
                                                <input type="number" name="qty" id="form-shipEntry-qty" style="width: 100%;"
                                                    class="form-control col-sm-12" required autocomplete="off" step="any">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Added new line ver20250211 -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Upload Document (.pdf)</label>
                                                <input type="file" name="doc" id="form-shipEntry-document"
                                                        class="form-control col-sm-12"
                                                        style="width: 100%;"
                                                        accept=".pdf"
                                                        >
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
        var index_url   = "{{ route('packageentry.index') }}";
        var post_url    = "{{ route('packageentry.store') }}";
        var show_url    = "{{ route('packageentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_shipEntry_title          = '#modal-shipEntry-title';

        const $form_shipEntry                = '#form-shipEntry';
        const $form_shipEntry_flag           = '#form-shipEntry-flag';
        const $form_shipEntry_mode           = '#form-shipEntry-mode';
        const $form_shipEntry_id             = '#form-shipEntry-id';
        const $form_shipEntry_entryDate      = '#form-shipEntry-date';
        const $form_shipEntry_bom            = '#form-shipEntry-bom';
        const $form_shipEntry_fgProduct      = '#form-shipEntry-fgProduct';
        const $form_shipEntry_soNo           = '#form-shipEntry-soNo';
        const $form_shipEntry_qty            = '#form-shipEntry-qty';
        const $form_shipEntry_balance        = '#form-shipEntry-balance';
        const $form_shipEntry_batch          = '#form-shipEntry-batch';
        const $form_shipEntry_document       = '#form-shipEntry-document';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_shipEntry).unbind().on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($form_shipEntry_mode).val();
                    var $balance = $($form_shipEntry_balance).val();
                    var $qty = $($form_shipEntry_qty).val();

                    if ($balance - $qty < 0){
                        Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: 'Product Balance < 0!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        return;
                    }

                    var $batchSelected = $($form_shipEntry_batch + ' option:selected' ).text();
                    var $parts = $batchSelected.split(':');
                    var $qtyStock = $parts[1].trim();

                    if ($qtyStock - $qty < 0){
                        Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: 'Batch Balance < 0!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        return;
                    }

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' PACKAGING ENTRY ?',
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

                                        $($modal_shipEntry).modal('hide');
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
                $(document).on('change', $form_shipEntry_fgProduct, function(){
                    var $idMaterial = $($form_shipEntry_fgProduct).val();

                    if ($idMaterial !== ''){
                        ajax_populateWipProduct($idMaterial);
                        ajax_populateBatchProduct($form_shipEntry_batch, $idMaterial);
                    } else {
                        $($form_shipEntry_bom).html("Product : N/A");
                    };
                });
                $(document).on('change', $form_shipEntry_batch, function(){
                    var $batchSelected = $($form_shipEntry_batch + ' option:selected' ).text();
                    var $parts = $batchSelected.split(':');
                    var $qty = $parts[1].trim();

                    $($form_shipEntry_qty).val($qty);
                });

            /* EVENT LISTENER ON CLICK */



        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
        function ajax_populateBatchProduct($tagId, $idMaterial, selectedValue=null){
            // Empty the dropdown
            $($tagId).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_activeBatchProduct',
                    idMaterial: $idMaterial
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        // Read data and create <option >
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].batch_no;
                            var populate_2 = response['data'][i].description;
                            if (selectedValue) {
                                if (populate_1 == selectedValue) {
                                    var option = "<option value='"+populate_1+"' selected='"+selectedValue+"'>"+populate_2+"</option>";
                                } else {
                                    var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                                }
                            } else {
                                var option = "<option value='"+populate_1+"'>"+populate_2+"</option>";
                            }
                            $($tagId).append(option);
                        }
                    }
                }
            });
        }
        function ajax_populateFgProduct($tagId, selectedValue=null){
            // Empty the dropdown
            $($tagId).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_activeFgProduct',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        // Read data and create <option >
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
                            $($tagId).append(option);
                        }
                    }
                }
            });
        };
        function ajax_populateWipProduct($idMaterial){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_wipMaterialByFgProduct',
                    idMaterial: $idMaterial
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($form_shipEntry_bom).html("Product : " + response['data'][0].wip_material);
                        $($form_shipEntry_balance).val(response['data'][0].balance);
                    }
                }
            });
        };


    /* FUNCTION DYNAMICS */



    /* FUNCTION INITIALIZATION */
        function initialize_shipEntry($flag=null, $mode=null, $id=null, $soNo=null, $qty=null, $idFgProduct=null){
            $($form_shipEntry_flag).val($flag);
            $($form_shipEntry_mode).val($mode);
            $($form_shipEntry_id).val($id);
            $($form_shipEntry_soNo).val($soNo);
            $($form_shipEntry_qty).val($qty);
            $($form_shipEntry_bom).html("Product : N/A");

            render_time_related_entry();
            ajax_populateFgProduct($form_shipEntry_fgProduct);
        };
        function render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($form_shipEntry_entryDate).val(currentDate);
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

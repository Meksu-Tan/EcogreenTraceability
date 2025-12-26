<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-pckEntry">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-pckEntry-title">PACKAGING ENTRY</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-pckEntry" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-pckEntry-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-pckEntry-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="balance" id="form-pckEntry-balance" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-pckEntry-mode" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Date</label>
                                                <input type="date" name="entryDate" id="form-pckEntry-date" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Packaging Product</label>
                                                <select name="fgProduct" id="form-pckEntry-fgProduct" style="width: 100%;" class="form-control" required>
                                                    <option value="">- Select Packaging Product -</option>
                                                </select>
                                                <label for="name" id="form-pckEntry-bom">Product : N/A</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-tank">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Source Sloc (auto select)</label>
                                                <select name="tank" id="form-pckEntry-tank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Tank -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Specific Source Sloc</label>
                                                <select name="tankNo[]" id="form-pckEntry-tankNo" style="width: 100%;" class="form-control" multiple="multiple" required>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">PO No</label>
                                                <input type="text" name="poNo" id="form-pckEntry-poNo" style="width: 100%;"
                                                            class="form-control col-sm-12" required autocomplete="off"
                                                            oninput="this.value = this.value.toUpperCase();">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Packaging Batch No</label>
                                                <input type="text" name="batchNo" id="form-pckEntry-batchNo" style="width: 100%;"
                                                            class="form-control col-sm-12" required autocomplete="off"
                                                            oninput="this.value = this.value.toUpperCase();">
                                                <label for="name">FB = Flexi bag | IS = Isotank | VS = Vessel</label>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Qty (MT)</label>
                                                <input type="number" name="qty" id="form-pckEntry-qty" style="width: 100%;"
                                                    class="form-control col-sm-12" required autocomplete="off" step="any">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-tank">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Destination Sloc (Auto - Based on Batch No)</label>
                                                <select name="warehouse" id="form-pckEntry-warehouse" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Tank -</option> -->
                                                </select>
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
        const $form_pckEntry_title          = '#modal-pckEntry-title';
        const $form_pckEntry                = '#form-pckEntry';

        const $form_pckEntry_flag           = '#form-pckEntry-flag';
        const $form_pckEntry_mode           = '#form-pckEntry-mode';
        const $form_pckEntry_id             = '#form-pckEntry-id';
        const $form_pckEntry_entryDate      = '#form-pckEntry-date';
        const $form_pckEntry_bom            = '#form-pckEntry-bom';
        const $form_pckEntry_fgProduct      = '#form-pckEntry-fgProduct';
        const $form_pckEntry_batchNo        = '#form-pckEntry-batchNo';
        const $form_pckEntry_qty            = '#form-pckEntry-qty';
        const $form_pckEntry_balance        = '#form-pckEntry-balance';
        const $form_pckEntry_poNo           = '#form-pckEntry-poNo';
        const $form_pckEntry_idTank         = '#form-pckEntry-tank';
        const $form_pckEntry_idTankNo       = '#form-pckEntry-tankNo';
        const $form_pckEntry_idWarehouse    = '#form-pckEntry-warehouse';

        let pckEntry_selectedTankTails = [];

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_pckEntry).unbind().on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($form_pckEntry_mode).val();
                    var $balance = $($form_pckEntry_balance).val();
                    var $qty = $($form_pckEntry_qty).val();

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

                                        $($modal_pckEntry).modal('hide');
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
                $(document).on('change', $form_pckEntry_fgProduct, function(){
                    var $idMaterialPck = $($form_pckEntry_fgProduct).val();
                    if ($idMaterialPck !== ''){
                        ajax_populateWipProduct($idMaterialPck);
                    } else {
                        $($form_pckEntry_bom).html("Product : N/A");
                    };
                });
                $(document).on('change', $form_pckEntry_idTank, function() {
                    var $idTank = $(this).val();
                    var $idMaterialPck = $($form_pckEntry_fgProduct).val();

                    if ($idTank && $idMaterialPck) {
                        ajax_updateBalanceByTank($idMaterialPck, $idTank);
                    }
                });
                $(document).on('change', $form_pckEntry_batchNo, function(e){
                    e.preventDefault();

                    $batchNo = $($form_pckEntry_batchNo).val();
                    ajax_populateWarehouse($form_pckEntry_idWarehouse, $batchNo);
                });
                $(document).on('change', $form_pckEntry_idTankNo, function () {
                    pckEntry_selectedTankTails = $(this).val() || [];
                });
                $(document).on('change', $form_pckEntry_idTank, function () {
                    let sloc = $(this).val();
                    ajax_populateTankNo($form_pckEntry_idTankNo, sloc, pckEntry_selectedTankTails);
                });


            /* EVENT LISTENER ON CLICK */



        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */
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
                            var populate_1 = response['data'][i].id_materialpck;
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
        function ajax_populateWipProduct($idMaterialPck){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_wipMaterialByFgProduct',
                    idMaterialPck: $idMaterialPck
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($form_pckEntry_bom).html("Product : " + response['data'][0].wip_material);
                        $($form_pckEntry_balance).val(response['data'][0].balance);

                        ajax_populateTankPackaging($form_pckEntry_idTank, response['data'][0].id_rundown);
                    }
                }
            });
        };
        function ajax_updateBalanceByTank($idMaterialPck, $idTank) {
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data: {
                    flag: 'get_wipMaterialByFgProduct',
                    idMaterialPck: $idMaterialPck,
                    tank: $idTank
                },
                success: function(response) {
                    if (response['data'] && response['data'].length > 0) {
                        var data = response['data'][0];
                        var balance = data.balance;
                        var wip_material = response['data'][0].wip_material;

                        $($form_pckEntry_balance).val(balance);

                        $($form_pckEntry_bom).html(
                            "Product : " + wip_material
                        );

                        Swal.fire({
                            position: 'top-end',
                            icon: 'info',
                            title: 'Balance updated: ' + balance + ' MT',
                            showConfirmButton: false,
                            timer: 1000
                        });
                    }
                }
            });
        }
        function ajax_populateTankPackaging(id, $rundownID, selectedValue=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank_pck',
                    rundownID: $rundownID,
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
                        // auto-select correct tank value
                        let valueToSelect = selectedValue ?? response.data[0]?.id_tank;
                        $(id).val(valueToSelect).trigger("change"); // this triggers the SLoc update
                    }
                }
            });
        };
        function ajax_populateTankNo(id, $sloc=null, selectedValues=null, options={}) {
            const $select = $(id);
            let selected = [];

            if (Array.isArray(selectedValues)) {
                selected = selectedValues.map(String);
            } else if (typeof selectedValues === 'string') {
                try {
                    selected = JSON.parse(selectedValues).map(String);
                } catch {
                    selected = [];
                }
            }

            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data: {
                    flag: 'get_cmbActiveSpecificTank',
                    sloc: $sloc,
            },
            success: function(response) {
                $select.empty();

                if (response.data && response.data.length) {
                    response.data.forEach(row => {
                        const isSelected = selected.includes(String(row.id_tank_tail));

                        const option = new Option(
                            row.tankNo,
                            row.id_tank_tail,
                            isSelected,
                            isSelected
                        );

                        $select.append(option);
                    });
                }
                if (!$select.hasClass("select2-hidden-accessible")) {
                    $select.select2({
                        placeholder: ' - Select Specific Sloc No -',
                        closeOnSelect: false,
                        allowClear: true,
                        width: '100%',
                        dropdownParent: options.dropdownParent || $select.closest(".modal"),
                    });
                }
                
                $select.trigger('change');
            }
        });
    }
        function ajax_populateWarehouse(id, $batchNo=null, selectedValue=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveWarehouse_pck',
                    batchNo: $batchNo,
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].id_warehouse;
                            var populate_2 = response['data'][i].warehouse;

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
        function initialize_pkcEntry($flag=null, $mode=null, $id=null, $batchNo=null, $qty=null, $idFgProduct=null, $poNo=null, $idTank=null, $idTankTail=null){
            $($form_pckEntry_flag).val($flag);
            $($form_pckEntry_mode).val($mode);
            $($form_pckEntry_id).val($id);
            $($form_pckEntry_batchNo).val($batchNo);
            $($form_pckEntry_poNo).val($poNo);
            $($form_pckEntry_qty).val($qty);
            $($form_pckEntry_bom).html("Product : N/A");

            render_time_related_entry();
            ajax_populateFgProduct($form_pckEntry_fgProduct);

            if ($mode == 'ADD' && pckEntry_selectedTankTails.length === 0) {
                ajax_populateTankNo($form_pckEntry_idTankNo, $idTank, $idTankTail);
            }
        };
        function render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($form_pckEntry_entryDate).val(currentDate);
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

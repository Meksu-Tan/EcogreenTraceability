<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-rm-trf">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Transfer to Feed Tank Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-rmTrfEntry" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-rmTrfEntry-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-rmTrfEntry-idHead" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-rmTrfEntry-mode" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Entry Number (Auto)</label>
                                                <input name="entry_no" id="form-rmTrfEntry-entryNo" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Date (Auto Detect)</label>
                                                <input type="date" name="entry_date" id="form-rmTrfEntry-entryDate" style="width: 100%;" class="form-control" required autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Source Sloc</label>
                                                <select name="sourceTank" id="form-rmTrfEntry-fromTank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Sloc -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Transfer Sloc</label>
                                                <select name="trfTank" id="form-rmTrfEntry-toTank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Sloc -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Material Document (SAP)</label>
                                                <input name="material_doc" id="form-rmTrfEntry-materialDoc" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Specific Source Sloc No</label>
                                                <select name="tankNo[]" id="form-rmTrfEntry-fromTankNo" style="width: 100%;" class="form-control" multiple="multiple">
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Specific Trf Sloc No</label>
                                                <select name="trfTankNo[]" id="form-rmTrfEntry-toTankNo" style="width: 100%;" class="form-control" multiple="multiple">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name" style="display: block;">&nbsp;</label>
                                                <button class="btn btn-dark" id="add-rmTrfEntry-material">Add RM & Qty</button>
                                                <button class="btn btn-primary" id="save-rmTrfEntry">Save Entry</button>
                                            </div>
                                        </div>
                                        <div class="col-md-5">

                                        </div>
                                        <div class="col-md-3" style="text-align:right">
                                            <div class="form-group">
                                                <label for="name">Total Qty (MT)</label>
                                                <input type="text" name="qty" id="form-rmTrfEntry-qty" style="width: 100%; text-align:right" class="form-control col-sm-12" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable no-footer" id="form-rmTrfEntry-detail" width="100%" role="grid" aria-describedby="table-1_info">
                                                <thead>
                                                    <tr>
                                                        <th width="7%">No</th>
                                                        <th>Action</th>
                                                        <th>Material</th>
                                                        <th>Qty (MT)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- <div class="form-group">
                                        <button class="btn btn-primary" id="save-rmTrfEntry">Save Entry</button>
                                    </div> -->
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
        const $form_rmTrfEntry                 = '#form-rmTrfEntry';

        const $txt_rmTrfEntry_flag             = '#form-rmTrfEntry-flag';
        const $txt_rmTrfEntry_mode             = '#form-rmTrfEntry-mode';
        const $txt_rmTrfEntry_idHead           = '#form-rmTrfEntry-idHead';

        const $txt_rmTrfEntry_number           = '#form-rmTrfEntry-entryNo';
        const $txt_rmTrfEntry_date             = '#form-rmTrfEntry-entryDate';
        const $cmb_rmTrfEntry_sourceTank       = '#form-rmTrfEntry-fromTank';
        const $cmb_rmTrfEntry_trfTank          = '#form-rmTrfEntry-toTank';
        const $cmb_rmTrfEntry_sourceTankNo     = '#form-rmTrfEntry-fromTankNo';
        const $cmb_rmTrfEntry_trfTankNo        = '#form-rmTrfEntry-toTankNo';
        const $txt_rmTrfEntry_materialDoc      = '#form-rmTrfEntry-materialDoc';

        const $btn_rmTrfEntry_save             = '#save-rmTrfEntry';
        const $btn_rmTrfEntry_material         = '#add-rmTrfEntry-material';
        const $txt_rmTrfEntry_qty              = '#form-rmTrfEntry-qty';
        const $txt_rmTrfEntry_detail           = '#form-rmTrfEntry-detail';

        const $btn_deactivateRmTrfMaterial     = '#destroy-rmentrytrf-material';

        let rmTrfEntry_selectedSourceTankTails = [];
        let rmTrfEntry_selectedTrfTankTails    = [];
        let rmTrfEntry_isReturningFromMaterial = false;

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_rmTrfEntry).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($txt_rmTrfEntry_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' RM TRF entry ?',
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

                                        $($modal_rmEntryTrf).modal('hide');
                                        initialize_page();

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });
            /* LISTENER ON CLICK FUNCTION */
                $(document).on('click', $btn_rmTrfEntry_material, function(e){
                    e.preventDefault();

                    $mode = 'ADD';
                    $idHead = null;
                    $idTail = null;
                    $entryNo = $($txt_rmTrfEntry_number).val();
                    $qty = null;
                    $idTankFeed = $($cmb_rmTrfEntry_trfTank).val();
                    $idTankStorage = $($cmb_rmTrfEntry_sourceTank).val();
                    $entryDate = $($txt_rmTrfEntry_date).val();
                    $materialDoc = $($txt_rmTrfEntry_materialDoc).val();
                    $idMaterial = null;

                    rmTrfEntry_isReturningFromMaterial = true;

                    initialize_modalRmEntryMaterial($mode, $idHead, $idTail, $entryNo, $qty, $idTankFeed,
                                                    $idTankStorage, $entryDate, $materialDoc, $idMaterial);
                    $($modal_rmEntryTrf_addMaterial).modal('show');
                });
                $(document).on('click', $btn_deactivateRmTrfMaterial, function(e){
                    e.preventDefault();

                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'De-Activate this data',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes!'
                    }).then((willDeleted) => {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });
                        if (willDeleted.value) {
                            ajax_deactivateRmEntryTrfMaterial($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });

            /* LISTENER ON MODAL STACK */
                $($modal_rmEntryTrf_addMaterial).on('show.bs.modal', function () {
                    if ( $($modal_rmEntryTrf).hasClass('show') ) {
                        $($modal_rmEntryTrf).css('opacity', 0.3);
                    }
                });
                $($modal_rmEntryTrf_addMaterial).on('hidden.bs.modal', function () {
                    if ( $($modal_rmEntryTrf).hasClass('show') ) {
                        $($modal_rmEntryTrf).css('opacity', 1);
                    }

                    if (rmTrfEntry_selectedSourceTankTails.length > 0) {
                        $($cmb_rmTrfEntry_sourceTankNo).val(rmTrfEntry_selectedSourceTankTails).trigger('change');
                    }

                    if (rmTrfEntry_selectedTrfTankTails.length > 0) {
                        $($cmb_rmTrfEntry_trfTankNo).val(rmTrfEntry_selectedTrfTankTails).trigger('change');
                    }

                    rmTrfEntry_isReturningFromMaterial = false;
                });
                $($modal_rmEntryTrf).on('hidden.bs.modal', function () {
                    rmTrfEntry_selectedSourceTankTails = [];
                    rmTrfEntry_selectedTrfTankTails = [];
                    $($cmb_rmTrfEntry_sourceTankNo).val(null).trigger('change');
                    $($cmb_rmTrfEntry_trfTankNo).val(null).trigger('change');
                });
                $(document).on('change', $cmb_rmTrfEntry_sourceTankNo, function () {
                    rmTrfEntry_selectedSourceTankTails = $(this).val() || [];
                });
                $(document).on('change', $cmb_rmTrfEntry_trfTankNo, function () {
                    rmTrfEntry_selectedTrfTankTails = $(this).val() || [];
                });

        });

    /* FUNCTION AJAX */
        function ajax_createRmNumberTrf($id){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_rmNewEntryNumberTrf'
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].rm_number);
                        ajax_dtMaterialList($txt_rmTrfEntry_detail, response['data'][0].rm_number, 'ADD');
                        ajax_getTotalQtyMaterial($txt_rmTrfEntry_qty, response['data'][0].rm_number, 'ADD');
                    }
                }
            });
        };
        function ajax_populateTankSource(id, selectedValue=null, selectedSpecific=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
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
                    ajax_populateSpecificTankSource($cmb_rmTrfEntry_sourceTankNo, 4, selectedSpecific);
                }
            });
        };
        function ajax_populateTankTrf(id, $sloc=null, selectedValue=null, selectedSpecific=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
            const prevValue = $(id).val();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank_trf',
                    sloc: $sloc,
                },
                success: function(response){
                    if (prevValue && prevValue === selectedValue) {
                        return;
                    }
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
                    if ($sloc === 'FEED') {
                        let selectedFeedTank = $(id).val();

                        ajax_populateSpecificTankTrf($cmb_rmTrfEntry_trfTankNo, selectedFeedTank, selectedSpecific);
                    }
                }
            });
        };
        function ajax_populateSpecificTankSource(id, $sloc=null, selectedValues=null, options={}) {
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
                    flag: 'get_cmbActiveSpecificSourceTank',
                    sloc: $sloc
                },
                success: function(response) {
                    const wasInitialized = $select.hasClass("select2-hidden-accessible");

                    $select.empty();

                    if (response.data && response.data.length) {
                        response.data.forEach(row => {
                            const option = new Option(
                                row.tankNo,
                                row.id_tank_tail,
                                false,
                                false
                            );

                            $select.append(option);
                        });
                    }
                    if (!wasInitialized) {
                        $select.select2({
                            placeholder: ' - Select Specific Source Sloc No -',
                            closeOnSelect: false,
                            allowClear: true,
                            width: '100%',
                            dropdownParent: options.dropdownParent || $select.closest(".modal"),
                        });
                    }
                    
                    if (selected.length) {
                        $select.val(selected).trigger('change');
                    }
                }
            });
        }
        function ajax_populateSpecificTankTrf(id, $sloc=null, selectedValues=null, options={}) {
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
                    flag: 'get_cmbActiveSpecificTrfTank',
                    sloc: $sloc,
                },
                success: function(response) {
                    const wasInitialized = $select.hasClass("select2-hidden-accessible");

                    $select.empty();

                    if (response.data && response.data.length) {
                        response.data.forEach(row => {
                            const option = new Option(
                                row.trfTankNo,
                                row.id_tank_tail,
                                false,
                                false
                            );

                            $select.append(option);
                        });
                    }
                    if (!wasInitialized) {
                        $select.select2({
                            placeholder: ' - Select Specific Trf Sloc No -',
                            closeOnSelect: false,
                            allowClear: true,
                            width: '100%',
                            dropdownParent: options.dropdownParent || $select.closest(".modal"),
                        });
                    }

                    if (selected.length) {
                        $select.val(selected).trigger('change');
                    }
                }
            });
        }
        function ajax_populateMaterialTrf(id, selectedValue=null){
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
        function ajax_getTotalQtyMaterial($id, $number, $mode){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_totalQtyMaterial',
                    number: $number,
                    mode: $mode
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].total);
                    }
                }
            });
        };
        function ajax_dtMaterialList($id, $number, $mode){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtMaterialList',
                        number: $number,
                        mode: $mode
                    }
                },
                order: [[ 0, 'desc']],
                responsive: true,
                columnDefs: [{
                    "searchable": false,
                    "orderable": false,
                    "targets": 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                    { data: 'material', name: 'material', className: 'text-left', width: "50%"},
                    { data: 'qty', name: 'qty', className: 'text-right'}
                ]
            });
        };
        function ajax_deactivateRmEntryTrfMaterial($href){
            $.ajax({
                url: $href,
                type: "POST",
                data: {
                    '_method': 'DELETE'
                },
                success: function(data) {
                    if (data.status == 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 500
                        })
                        initialize_modalRmTrfEntry($($txt_rmTrfEntry_mode).val(), $($txt_rmTrfEntry_number).val());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        })
                    }
                }
            })
        };

    /* FUNCTION INIT */
        function initialize_modalRmTrfEntry($mode, $trf_number=null, $idHead=null, $entryDate=null,
                                            $idTankSource=null, $idTankTrf=null, $sourceTankTails=null, 
                                            $trfTankTails=null, $materialDoc=null,
                                            $idMaterial=null){

            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($txt_rmTrfEntry_flag).val('post_rmTrfEntry');
            $($txt_rmTrfEntry_mode).val($mode);
            $($txt_rmTrfEntry_idHead).val($idHead);
            $($txt_rmTrfEntry_materialDoc).val($materialDoc);

            if (!rmTrfEntry_isReturningFromMaterial) {
                ajax_populateTankSource($cmb_rmTrfEntry_sourceTank, $idTankSource, $sourceTankTails);
                ajax_populateTankTrf($cmb_rmTrfEntry_trfTank, 'FEED', $idTankTrf, $trfTankTails);
            }
            ajax_getTotalQtyMaterial($txt_rmTrfEntry_qty, $trf_number, $mode);

            if ($mode == 'ADD'){
                $($txt_rmTrfEntry_date).val(currentDate);
                ajax_createRmNumberTrf($txt_rmTrfEntry_number);

            } else if ($mode == 'UPDATE'){
                $($txt_rmTrfEntry_number).val($trf_number);
                $($txt_rmTrfEntry_date).val($entryDate);
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

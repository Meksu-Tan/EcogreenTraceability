<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-blendingEntry">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span>Blending Entry</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-blendingEntry" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-blendingEntry-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="idHead" id="form-blendingEntry-idHead" class="form-control text-uppercase" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-blendingEntry-mode" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="name">Entry Number (Auto)</label>
                                                <input name="entry_no" id="form-blendingEntry-entryNo" class="form-control text-uppercase" required readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="name">Date (Auto Detect)</label>
                                                <input type="date" name="entry_date" id="form-blendingEntry-entryDate" style="width: 100%;" class="form-control" required autocomplete="off">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Material Document (SAP)</label>
                                                <input name="material_doc" id="form-blendingEntry-materialDoc" class="form-control text-uppercase" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Blended Material</label>
                                                <select name="id_material" id="form-blendingEntry-result" style="width: 100%;" class="form-control" required>
                                                    <option value="-">- Select Material -</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Storage Location (SLoc)</label>
                                                <select name="tank" id="form-blendingEntry-tank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Tank -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name">Specific Storage Location (SLoc)</label>
                                                <select name="tankNo[]" id="form-blendingEntry-tankNo" style="width: 100%;" class="form-control" multiple="multiple">
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="name" style="display: block;">&nbsp;</label>
                                                <button class="btn btn-dark" id="add-blendingEntry-material">Add Blend Source & Qty</button>
                                                <button class="btn btn-primary" id="save-blendingEntry">Save Entry</button>
                                            </div>
                                        </div>
                                        <div class="col-md-5">

                                        </div>
                                        <div class="col-md-3" style="text-align:right">
                                            <div class="form-group">
                                                <label for="name">Total Qty (MT)</label>
                                                <input type="text" name="qty" id="form-blendingEntry-qty" style="width: 100%; text-align:right" class="form-control col-sm-12" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable no-footer" id="form-blendingEntry-detailTbl" width="100%" role="grid" aria-describedby="table-1_info">
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
                                        <button class="btn btn-primary" id="save-blendingEntry">Save Entry</button>
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
        var index_url   = "{{ route('blending.index') }}";
        var post_url    = "{{ route('blending.store') }}";
        var show_url    = "{{ route('blending.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_blendingEntry                   = '#form-blendingEntry';

        const $form_blendingEntry_flag              = '#form-blendingEntry-flag';
        const $form_blendingEntry_idHead            = '#form-blendingEntry-idHead';
        const $form_blendingEntry_mode              = '#form-blendingEntry-mode';
        const $form_blendingEntry_entryNo           = '#form-blendingEntry-entryNo';
        const $form_blendingEntry_qty               = '#form-blendingEntry-qty';
        const $form_blendingEntry_detailTbl         = '#form-blendingEntry-detailTbl';
        const $form_blendingEntry_entryDate         = '#form-blendingEntry-entryDate';
        const $form_blendingEntry_materialDoc       = '#form-blendingEntry-materialDoc';
        const $form_blendingEntry_blendResult       = '#form-blendingEntry-result';
        const $form_blendingEntry_idTank            = '#form-blendingEntry-tank';
        const $form_blendingEntry_idTankNo          = '#form-blendingEntry-tankNo';

        const $btn_blendingEntry_material           = '#add-blendingEntry-material';
        const $btn_blendingEntry_save               = '#save-blendingEntry';
        const $btn_blendingEntry_delete             = '#destroy-blendingEntry-material';

        let blendingEntry_selectedTankTails = [];

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_blendingEntry).unbind().on('submit', function(e) {
                    e.preventDefault();
                    var formData = new FormData(this);
                    var $mode = $($form_blendingEntry_mode).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' BLENDING entry ?',
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

                                        $($modal_blendEntry).modal('hide');
                                        initialize_page();

                                    } else {
                                        Swal.fire(data.message, "", "error");
                                    }
                                }
                            });
                        }
                    });

                });

            /* LISTENER ON CLICK | CHANGE FUNCTION */
                $(document).on('change', $form_blendingEntry_blendResult, function(e){
                    e.preventDefault();
                    $mode = $($form_blendingEntry_mode).val();

                    if ($mode == 'ADD'){
                        $id_material = $($form_blendingEntry_blendResult).val();
                        $entryNo = $($form_blendingEntry_entryNo).val();
                        ajax_createEntryNo($form_blendingEntry_entryNo, $id_material);
                        ajax_dtMaterialList($form_blendingEntry_detailTbl, null, $entryNo, $mode);
                        ajax_populateTankRundown($form_blendingEntry_idTank, $id_material);
                    }
                });
                $(document).on('click', $btn_blendingEntry_material, function(e){
                    e.preventDefault();

                    $idMaterial = $($form_blendingEntry_blendResult).val();
                    $materialDoc = $($form_blendingEntry_materialDoc).val();
                    $entryDate = $($form_blendingEntry_entryDate).val();
                    $entryNo = $($form_blendingEntry_entryNo).val();
                    $mode = $($form_blendingEntry_mode).val();
                    $idHead = $($form_blendingEntry_idHead).val();
                    $idTank = $($form_blendingEntry_idTank).val();

                    if ($idMaterial == '-'){
                        Swal.fire({
                            position: 'top-end',
                            icon: 'error',
                            title: 'Select Blended Material !',
                            showConfirmButton: false,
                            timer: 1000
                        });
                        return;

                    } else {
                        $($modal_blendEntry_material).modal('show');
                        initializeBlendEntryMaterial($mode, $idHead, $idMaterial, $materialDoc, $entryDate, $entryNo, $idTank);
                    }
                });
                $(document).on('click', $btn_blendingEntry_delete, function(e){
                    e.preventDefault();

                    var $href = $(this).attr('data-href');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete this data',
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
                            ajax_deleteBlendingMaterial($href);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });

            /* LISTENER ON MODAL STACK */
                $($modal_blendEntry_material).on('show.bs.modal', function () {
                    if ( $($modal_blendEntry).hasClass('show') ) {
                        $($modal_blendEntry).css('opacity', 0.3);
                    }
                });
                $($modal_blendEntry_material).on('hidden.bs.modal', function () {
                    if ( $($modal_blendEntry).hasClass('show') ) {
                        $($modal_blendEntry).css('opacity', 1);
                    }

                    if (blendingEntry_selectedTankTails.length > 0) {
                        $($form_blendingEntry_idTankNo).val(blendingEntry_selectedTankTails).trigger('change');
                    }
                });
                $($modal_blendEntry).on('hidden.bs.modal', function () {
                    blendingEntry_selectedTankTails = [];
                    $($form_blendingEntry_idTankNo).val(null).trigger('change');
                });
                $(document).on('change', $form_blendingEntry_idTankNo, function () {
                    blendingEntry_selectedTankTails = $(this).val() || [];
                });
                $(document).on('change', $form_blendingEntry_idTank, function () {
                    let sloc = $(this).val();
                    ajax_populateSpecificTankRundown($form_blendingEntry_idTankNo, sloc, blendingEntry_selectedTankTails);
                });

        });

    /* FUNCTION AJAX */
        function ajax_cmbMaterial(id, selectedValue=null){
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
        function ajax_createEntryNo($id, $id_material){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_newBlendingEntryNo',
                    id_material: $id_material
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].entryNo);
                        ajax_dtMaterialList($form_blendingEntry_detailTbl, null, response['data'][0].entryNo, 'ADD');
                        ajax_getTotalQtyMaterial($form_blendingEntry_qty, null, response['data'][0].entryNo, 'ADD');
                    }
                }
            });
        };
        function ajax_getTotalQtyMaterial($id, $idHead=null, $entryNo=null, $mode=null){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_totalQtyMaterial',
                    idHead: $idHead,
                    entryNo: $entryNo,
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
        function ajax_dtMaterialList($id, $idHead=null, $entryNo=null, $mode=null){
            $($id).DataTable().destroy();

            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                ajax: {
                    url: show_url,
                    data: {
                        flag: 'get_dtMaterialList',
                        idHead: $idHead,
                        entryNo: $entryNo,
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
        function ajax_deleteBlendingMaterial($href){
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
                        $mode = $($form_blendingEntry_mode).val();
                        $entryNo = $($form_blendingEntry_entryNo).val();
                        ajax_dtMaterialList($form_blendingEntry_detailTbl, null, $entryNo, $mode);
                        ajax_getTotalQtyMaterial($form_blendingEntry_qty, null, $entryNo, $mode);

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
        function ajax_populateTankRundown(id, $idMaterial, selectedValue=null){
            $(id).empty();

            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data: {
                    flag: 'get_cmbActiveTank_rundown',
                    idMaterial: $idMaterial,
                },
                success: function(response) {

                    if (response.data) {
                        response.data.forEach(row => {
                        $(id).append(
                            $("<option>", {
                                value: row.id_tank,
                                text: row.tank
                            })
                        );
                    });

                        // auto-select correct tank value
                        let valueToSelect = selectedValue ?? response.data[0]?.id_tank;
                        $(id).val(valueToSelect).trigger("change"); // this triggers the SLoc update
                    }
                }
            });
        }

        function ajax_populateSpecificTankRundown(id, sloc=null, selectedValues=null, options={}) {
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
                    flag: 'get_cmbActiveSpecificTank_rundown',
                    sloc: sloc,
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

    /* FUNCTION INIT */
        function initializeBlendEntry($mode, $idHead=null, $blendResult=null, $materialDoc=null, $entryDate=null, $entryNo=null, $idTank=null, $idTankTail=null){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($form_blendingEntry_flag).val('post_blendingEntry');
            $($form_blendingEntry_mode).val($mode);
            $($form_blendingEntry_idHead).val($idHead);
            $($form_blendingEntry_materialDoc).val($materialDoc);
            $($form_blendingEntry_entryNo).val($entryNo);

            ajax_cmbMaterial($form_blendingEntry_blendResult, $blendResult);
            ajax_dtMaterialList($form_blendingEntry_detailTbl, $idHead, $entryNo, $mode);
            ajax_getTotalQtyMaterial($form_blendingEntry_qty, $idHead, $entryNo, $mode);

            if ($mode == 'ADD'){
                $($form_blendingEntry_qty).val('0');
                $($form_blendingEntry_entryDate).val(currentDate);
            } else if ($mode == 'UPDATE'){
                $($form_blendingEntry_entryDate).val($entryDate);
            }

            if ($mode == 'ADD' && blendingEntry_selectedTankTails.length === 0) {
                ajax_populateSpecificTankRundown($form_blendingEntry_idTankNo, $idTank, $idTankTail);
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

<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-rundown-material">
    <div class="modal-dialog" role="document" style="max-width: 700px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-rundown-title">RUNDOWN ENTRY</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-rundown-material" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-rundown-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-rundown-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="feature" id="form-rundown-feature" class="form-control text-uppercase" required>
                                        <input type="hidden" name="rundown_id" id="form-rundown-rundownId" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id_material" id="form-rundown-materialId" class="form-control text-uppercase" required>
                                        <input type="hidden" name="tag_number" id="form-rundown-tagNumber" class="form-control text-uppercase">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Rundown entry MUST BE in the SAME DAY as Feed entry! (Check Feed BATCH/TRACE NUMBER for system date - xYYMMDDxxxx)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Rundown Trace No</label>
                                                <input name="batch_no" id="form-rundown-batchNo" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-rundown-mode" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-lastRundown">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Last Rundown (MT) <span id="form-rundown-dataValue">-NORMAL-</span></label>
                                                <input type="number" name="last_rundown" id="form-rundown-last" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off" readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Last Entry Date <span id="form-rundown-dataDate">-NORMAL-</span></label>
                                                <input type="date" name="last_entryDate" id="form-rundown-lastEntryDate" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off" readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-tank">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="tank" id="form-rundown-tank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Tank -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="tankNo[]" id="form-rundown-tankNo" style="width: 100%;" class="form-control" multiple="multiple" required>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Current Rundown (MT)</label>
                                                <input type="number" name="curr_rundown" id="form-rundown-current" style="width: 100%;" class="form-control col-sm-12" step="any" required autocomplete="off">
                                                <label for="name" id="label-rundown-current" style="display:none;">DCS Quantifier is available</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Current Entry Date</label>
                                                <input type="date" name="curr_entryDate" id="form-rundown-currEntryDate" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-rundown">Save</button>
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
        var index_url   = "{{ route('wipentry.index') }}";
        var post_url    = "{{ route('wipentry.store') }}";
        var show_url    = "{{ route('wipentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_rundown_title          = '#modal-rundown-title';

        const $form_rundown_material       = '#form-rundown-material';
        const $form_rundown_flag           = '#form-rundown-flag';
        const $form_rundown_id             = '#form-rundown-id';
        const $form_rundown_mode           = '#form-rundown-mode';
        const $form_rundown_last           = '#form-rundown-last';
        const $form_rundown_lastEntryDate  = '#form-rundown-lastEntryDate';
        const $form_rundown_curr           = '#form-rundown-current';
        const $form_rundown_currEntryDate  = '#form-rundown-currEntryDate';
        const $form_rundown_batchNo        = '#form-rundown-batchNo';
        const $form_rundown_rundownId      = '#form-rundown-rundownId';
        const $form_rundown_materialId     = '#form-rundown-materialId';
        const $form_rundown_dataValue      = '#form-rundown-dataValue';
        const $form_rundown_dataDate       = '#form-rundown-dataDate';
        const $form_rundown_feature        = '#form-rundown-feature';
        const $form_rundown_idTank         = '#form-rundown-tank';
        const $form_rundown_tagNumber      = '#form-rundown-tagNumber';
        const $label_rundown_curr          = '#label-rundown-current';
        const $form_rundown_idTankNo       = '#form-rundown-tankNo';

        const $div_lastRundown             = '#div-lastRundown';

        let runddownEntry_selectedTankTails = [];

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_rundownMaterial();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_rundown_material).unbind().on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($form_rundown_mode).val();
                    var $title = $($form_rundown_title).html();

                    var $last_rundown = Number($($form_rundown_last).val());
                    var $curr_rundown = Number($($form_rundown_curr).val());
                    if ($last_rundown > $curr_rundown){
                        Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: 'Current Rundown must be bigger than Last Rundown!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        return;
                    }

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

                                        $($modal_rundownMaterial).modal('hide');
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
                $($form_rundown_currEntryDate).on('change', function(){
                    if ($($form_rundown_tagNumber).val() !== null){
                        ajax_getQuantifierDataRundown($($form_rundown_tagNumber).val(), $($form_rundown_currEntryDate).val());
                    }
                });
                $(document).on('change', $form_rundown_idTankNo, function () {
                    rundownEntry_selectedTankTails = $(this).val() || [];
                });
                $(document).on('change', $form_rundown_idTank, function () {
                    let sloc = $(this).val();
                    ajax_populateSpecificTankFeed($form_rundown_idTankNo, sloc, rundownEntry_selectedTankTails);
                });

            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateRundownBatchNo($id, $rundownID=null){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_rundownNewBatchNumber',
                    rundownID: $rundownID
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].rundown_number);
                    }
                }
            });
        };
        function ajax_populateLastRundown($id1, $id2, $rundownID){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_rundownLastBatch',
                    rundownID: $rundownID,
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        var $type = response['data'][0].status;
                        if ($type == '-QTF-'){
                            $($div_lastRundown).hide();
                        } else {
                            $($div_lastRundown).show();
                        };
                        $($id1).val(response['data'][0].curr_qtf);
                        $($id2).val(response['data'][0].entry_date);
                        $($form_rundown_dataValue).html(response['data'][0].status);
                        $($form_rundown_dataDate).html(response['data'][0].status);
                    }
                }
            });
        };
        function ajax_populateTankRundown(id, $rundownID, selectedValue=null){
            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank_rundown',
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

                        let valueToSelect = selectedValue ?? response.data[0]?.id_tank;
                        $(id).val(valueToSelect).trigger("change"); // this triggers the SLoc update
                    }
                }
            });
        };
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
                    flag: 'get_cmbActiveSpecificTank_trf',
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
        function ajax_getQuantifierDataRundown($tagNumber, $date){

            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_quantifierData',
                    tagNumber: $tagNumber,
                    date: $date
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($form_rundown_curr).val(response['data'][0].value);
                        $($label_rundown_curr).html('DCS data at ' + response['data'][0].timestamp);
                    }
                }
            });
        };

    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_rundownMaterial($title=null, $mode=null, $flag=null, $rundownID=null, $id=null, $quantifierDCS=null, $idTank=null, $idTankTail=null){
            $($form_rundown_flag).val($flag);
            $($form_rundown_id).val($id);
            $($form_rundown_mode).val($mode);
            $($form_rundown_title).html($title);
            $($form_rundown_rundownId).val($rundownID);
            $($form_rundown_feature).val($title);
            $($form_rundown_batchNo).val('');
            $($form_rundown_curr).val('0');
            $($form_rundown_last).val('0');
            $($form_rundown_currEntryDate).val();
            $($form_rundown_lastEntryDate).val();

            render_time_related_entry();
            ajax_populateRundownBatchNo($form_rundown_batchNo, $rundownID);
            ajax_populateLastRundown($form_rundown_last, $form_rundown_lastEntryDate, $rundownID);
            ajax_populateTankRundown($form_rundown_idTank, $rundownID);
            ajax_populateSpecificTankRundown($form_rundown_idTankNo, $idTank, $idTankTail);

            if ($quantifierDCS != null){
                $($form_rundown_tagNumber).val($quantifierDCS);
                ajax_getQuantifierDataRundown($quantifierDCS, $($form_rundown_currEntryDate).val());
                $($label_rundown_curr).show(); // tampilkan label
            } else {
                $($label_rundown_curr).hide(); // sembunyikan label
            }
        };

        function render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($form_rundown_currEntryDate).val(currentDate);
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

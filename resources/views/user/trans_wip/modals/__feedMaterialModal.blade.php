<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-feed-material">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-feed-title">CPKO FEEDS ENTRY</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-feed-material" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-feed-flag" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-feed-id" class="form-control text-uppercase" required>
                                        <input type="hidden" name="feature" id="form-feed-feature" class="form-control text-uppercase" required>
                                        <input type="hidden" name="feed_id" id="form-feed-feedId" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id_material" id="form-feed-materialId" class="form-control text-uppercase" required>
                                        <input type="hidden" name="tag_number" id="form-feed-tagNumber" class="form-control text-uppercase">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Feed Trace No</label>
                                                <input name="batch_no" id="form-feed-batchNo" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Entry Mode</label>
                                                <input name="mode" id="form-feed-mode" class="form-control text-uppercase" required readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-lastFeed">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Last Feed (MT) <span id="form-feed-dataValue">-NORMAL-</span></label>
                                                <input type="number" name="last_feed" id="form-feed-last" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off" readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Last Entry Date <span id="form-feed-dataDate">-NORMAL-</span></label>
                                                <input type="date" name="last_entryDate" id="form-feed-lastEntryDate" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off" readonly>
                                                <p class="text-danger"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="div-tank">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Sloc</label>
                                                <select name="tank" id="form-feed-tank" style="width: 100%;" class="form-control" required>
                                                    <!-- <option value="">- Select Tank -</option> -->
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="name">Specific Sloc</label>
                                                <select name="tankNo[]" id="form-feed-tankNo" style="width: 100%;" class="form-control" multiple="multiple" required>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Current Feed (MT) </label>
                                                <input type="number" name="curr_feed" id="form-feed-current" style="width: 100%;" class="form-control col-sm-12" step="any" required autocomplete="off">
                                                <label for="name" id="label-feed-current" style="display:none;">DCS Quantifier is available</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Current Entry Date</label>
                                                <input type="date" name="curr_entryDate" id="form-feed-currEntryDate" style="width: 100%;" class="form-control col-sm-12" required autocomplete="off">
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
        var index_url   = "{{ route('wipentry.index') }}";
        var post_url    = "{{ route('wipentry.store') }}";
        var show_url    = "{{ route('wipentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_feed_title          = '#modal-feed-title';

        const $form_feed_material       = '#form-feed-material';
        const $form_feed_flag           = '#form-feed-flag';
        const $form_feed_id             = '#form-feed-id';
        const $form_feed_mode           = '#form-feed-mode';
        const $form_feed_last           = '#form-feed-last';
        const $form_feed_lastEntryDate  = '#form-feed-lastEntryDate';
        const $form_feed_tank           = '#form-feed-tank';
        const $form_feed_curr           = '#form-feed-current';
        const $form_feed_currEntryDate  = '#form-feed-currEntryDate';
        const $form_feed_batchNo        = '#form-feed-batchNo';
        const $form_feed_feedId         = '#form-feed-feedId';
        const $form_feed_materialId     = '#form-feed-materialId';
        const $form_feed_dataValue      = '#form-feed-dataValue';
        const $form_feed_dataDate       = '#form-feed-dataDate';
        const $form_feed_feature        = '#form-feed-feature';
        const $form_feed_tagNumber      = '#form-feed-tagNumber';
        const $label_feed_curr          = '#label-feed-current';
        const $form_feed_tankNo         = '#form-feed-tankNo';

        const $div_tank                 = '#div-tanks';
        const $div_lastFeed             = '#div-lastFeed';

        let feedEntry_selectedTankTails = [];

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_feedMaterial();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_feed_material).unbind().on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($form_feed_mode).val();
                    var $title = $($form_feed_title).html();

                    var $last_feed = Number($($form_feed_last).val());
                    var $curr_feed = Number($($form_feed_curr).val());
                    if ($last_feed > $curr_feed){
                        Swal.fire({
                                position: 'top-end',
                                icon: 'warning',
                                title: 'Current Feed must be bigger than Last Feed!',
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

                                        $($modal_feedMaterial).modal('hide');
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
                $($form_feed_currEntryDate).on('change', function(){
                    if ($($form_feed_tagNumber).val() != null){
                        ajax_getQuantifierDataFeed($($form_feed_tagNumber).val(), $($form_feed_currEntryDate).val());
                    }
                });
                $(document).on('change', $form_feed_tankNo, function () {
                    feedEntry_selectedTankTails = $(this).val() || [];
                });
                $(document).on('change', $form_feed_tank, function () {
                    let sloc = $(this).val();
                    ajax_populateSpecificTankFeed($form_feed_tankNo, sloc, feedEntry_selectedTankTails);
                });

            /* EVENT LISTENER ON CLICK */



        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateTankFeed(id, $feedID, selectedValue=null){
            if ($feedID == '01'){
                var $sloc = 'TRF';
            } else {
                var $sloc = 'WIP';
            }

            // Empty the dropdown
            // $(id).find('option').not(':first').remove();
            $(id).find('option').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveTank_trf',
                    feedID: $feedID,
                    sloc: $sloc,
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
        function ajax_populateSpecificTankFeed(id, sloc=null, selectedValues=null, options={}) {
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
        function ajax_populateFeedBatchNo($id, $feedID=null){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_feedNewBatchNumber',
                    feedID: $feedID
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $($id).val(response['data'][0].feed_number);
                    }
                }
            });
        };
        function ajax_populateLastFeed($id1, $id2, $feedID=null){
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_feedLastBatch',
                    feedID: $feedID,
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        var $type = response['data'][0].status;
                        if ($type == '-QTF-'){
                            $($div_lastFeed).hide();
                        } else {
                            $($div_lastFeed).show();
                        };
                        $($id1).val(response['data'][0].curr_qtf);
                        $($id2).val(response['data'][0].entry_date);
                        $($form_feed_dataValue).html(response['data'][0].status);
                        $($form_feed_dataDate).html(response['data'][0].status);
                    }
                }
            });
        };
        function ajax_getQuantifierDataFeed($tagNumber, $date){
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
                        $($form_feed_curr).val(response['data'][0].value);
                        $($label_feed_curr).html('DCS data at ' + response['data'][0].timestamp);
                    }
                }
            });
        };

    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_feedMaterial($title=null, $mode=null, $flag=null, $feedID=null, $id=null, $quantifierDCS=null, $idTank=null, $idTankTail=null){
            $($form_feed_flag).val($flag);
            $($form_feed_id).val($id);
            $($form_feed_mode).val($mode);
            $($form_feed_title).html($title);
            $($form_feed_feedId).val($feedID);
            $($form_feed_feature).val($title);
            $($form_feed_batchNo).val('');
            $($form_feed_curr).val('0');
            $($form_feed_last).val('0');
            $($form_feed_currEntryDate).val();
            $($form_feed_lastEntryDate).val();
            $($form_feed_tagNumber).val($quantifierDCS);

            feed_render_time_related_entry();
            ajax_populateTankFeed($form_feed_tank, $feedID);
            ajax_populateFeedBatchNo($form_feed_batchNo, $feedID);
            ajax_populateLastFeed($form_feed_last, $form_feed_lastEntryDate, $feedID);
            ajax_populateSpecificTankFeed($form_feed_tankNo, $idTank, $idTankTail);

            if ($id == 'NON-CPKO'){
                $($div_tank).hide();
                $($form_feed_tank).prop('required', false);
            } else {
                $($div_tank).show();
                $($form_feed_tank).prop('required', true);
            }

            if ($quantifierDCS != null){
                ajax_getQuantifierDataFeed($quantifierDCS, $($form_feed_currEntryDate).val());
                $($label_feed_curr).show(); // tampilkan label
            } else {
                $($label_feed_curr).hide(); // sembunyikan label
            }
        };
        function feed_render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');
            var currentTime = time_format(new Date());

            $($form_feed_currEntryDate).val(currentDate);
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

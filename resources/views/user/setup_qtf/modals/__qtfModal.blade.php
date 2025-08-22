quantifier<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-quantifier">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-headerSectionQuantifier">Reset Quantifier</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="form-quantifier" method="post">
                                    @csrf
                                    <div class="form-group">
                                        <input type="hidden" name="flag" id="form-flagQuantifier" class="form-control text-uppercase" required>
                                        <input type="hidden" name="id" id="form-idQuantifier" class="form-control text-uppercase" required>
                                        <label for="name">Entry Mode</label>
                                        <input name="mode" id="form-modeQuantifier" class="form-control text-uppercase" required readonly>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Reset Date</label>
                                        <input type="date" name="reset_date" id="form-resetdateQuantifier" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Flowmeter</label>
                                        <select name="flowmeter" id="form-flowmeterQuantifier" style="width: 100%;" class="form-control">
                                            <option value="">- All Flowmeter -</option>
                                        </select>
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Reset Value</label>
                                        <input type="number" name="value" id="form-valueQuantifier" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="name">Remark</label>
                                        <input type="text" name="remark" id="form-remarkQuantifier" style="width: 100%;" class="form-control" required autocomplete="off">
                                        <p class="text-danger"></p>
                                    </div>
                                    <div class="form-group">
                                        <button class="btn btn-primary" id="save-quantifier">Save</button>
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
        var index_url   = "{{ route('qtfsetup.index') }}";
        var post_url    = "{{ route('qtfsetup.store') }}";
        var show_url    = "{{ route('qtfsetup.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $form_quantifier              = '#form-quantifier';

        const $txt_flagQuantifier           = '#form-flagQuantifier';
        const $txt_modeQuantifier           = '#form-modeQuantifier';
        const $txt_idQuantifier             = '#form-idQuantifier';

        const $txt_resetdateQuantifier      = '#form-resetdateQuantifier';
        const $cmb_flowmeterQuantifier      = '#form-flowmeterQuantifier';
        const $txt_remarkQuantifier         = '#form-remarkQuantifier';
        const $txt_valueQuantifier          = '#form-valueQuantifier';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

                initialize_modalQuantifier();

            /* LISTENER ON SUBMIT FUNCTION */
                $($form_quantifier).unbind().on('submit', function(event) {
                    event.preventDefault();

                    var formData = new FormData(this);
                    var $mode = $($txt_modeQuantifier).val();

                    Swal.fire({
                        title: 'Confirm Action',
                        text: $mode + ' Reset Quantifier?',
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

                                        $($dt_quantifier).DataTable().ajax.reload();
                                        $($modal_quantifier).modal('hide');

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
        function populate_cmbFlowmeter(id, selectedValue=null){
            // Empty the dropdown
            $(id).find('option').not(':first').remove();
            // AJAX request
            $.ajax({
                url: show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_cmbActiveFlowmeter',
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        for(var i=0; i<len; i++){
                            var populate_1 = response['data'][i].qtf;
                            var populate_2 = response['data'][i].qtf;
                            if (selectedValue) {
                                if (populate_2 == selectedValue) {
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

    /* FUNCTION INITIALIZATION */
        function initialize_modalQuantifier(){
            $($txt_remarkQuantifier).val('');
            $($txt_valueQuantifier).val(0);

            render_time_related_entry();
            populate_cmbFlowmeter($cmb_flowmeterQuantifier);
        };

        function render_time_related_entry(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($txt_resetdateQuantifier).val(currentDate);
        };
        function time_format(d) {
            hours = format_two_digits(d.getHours());
            minutes = format_two_digits(d.getMinutes());
            return hours + ":" + minutes;
        };
        function format_two_digits(n) {
            return n < 10 ? '0' + n : n;
        };

</script>
@endpush

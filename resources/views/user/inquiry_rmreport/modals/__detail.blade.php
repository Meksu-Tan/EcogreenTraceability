<style>
    .custom-modal-width {
        max-width: 1200px; /* Sesuaikan dengan kebutuhan */
        width: 100%;
    }
</style>

<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-viewDetail">
    <div class="modal-dialog custom-modal-width" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-viewDetail-header">Detail RM Traceability</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <input type="hidden" name="batchSap" id="modal-viewDetail-batchSap" class="form-control text-uppercase" required>
                        <div class="card">
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="tab1-tab" data-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">On-WIP</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="tab2-tab" data-toggle="tab" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">On-PRODUCT</a>
                                    </li>
                                </ul>
                                <!-- Tab panes -->
                                <div class="tab-content mt-3">
                                    <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable" id="tableReportDetail1" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Sloc</th>
                                                        <th>Material</th>
                                                        <th>IN Qty (MT)</th>
                                                        <th>OUT Qty (MT)</th>
                                                        <th>Balance (MT)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Dynamic content here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable" id="tableReportDetail2" width="100%">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Sloc</th>
                                                        <th>Material</th>
                                                        <th>IN Qty (MT)</th>
                                                        <th>OUT Qty (MT)</th>
                                                        <th>Balance (MT)</th>
                                                        <th>Shipment (click for detail)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Dynamic content here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div> <!-- end tab-content -->
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
        var index_url   = "{{ route('rmreport.index') }}";
        var post_url    = "{{ route('rmreport.store') }}";
        var show_url    = "{{ route('rmreport.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $modal_entry_batchSap     = '#modal-viewDetail-batchSap';
        const $modal_tableReportDetail1 = '#tableReportDetail1';
        const $modal_tableReportDetail2 = '#tableReportDetail2';
        const $modal_header             = '#modal-viewDetail-header';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
            /* ON CLICK HANDLER */
                $(document).on('click', '.badge[data-so_no]', function(e) {
                    e.preventDefault();

                    var $batchNo = $(this).attr('data-batch_no');
                    var $soNo = $(this).attr('data-so_no');

                    $($modal_shipDataShipment).modal('show');
                    initialize_shipData_modal($soNo, $batchNo);
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */



    /* FUNCTION AJAX */



     /* FUNCTION DYNAMICS */



    /* FUNCTION INITIALIZATION */



    /* FUNCTION AUTO-REFRESH */


</script>
@endpush

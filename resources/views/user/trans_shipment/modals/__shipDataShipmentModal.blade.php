<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-shipData">
    <div class="modal-dialog" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-shipData-title">SHIPMENT OVERVIEW</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Customer Code</label>
                                            <input type="text" id="page-shipData-customerCode" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="name">Customer Name</label>
                                            <input type="text" id="page-shipData-customerName" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">PO Number</label>
                                            <input type="text" id="page-shipData-poNumber" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">PRO INVOICE</label>
                                            <input type="text" id="page-shipData-proInvoice" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">ZBATCH</label>
                                            <input type="text" id="page-shipData-zbatch" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="name">Container Number</label>
                                            <div id="page-shipData-containerNo"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="name">Nett Weight (MT)</label>
                                            <input type="text" id="page-shipData-netWeight" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="name">Date Depart</label>
                                            <input type="text" id="page-shipData-dateDepart" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Port Discharge</label>
                                            <input type="text" id="page-shipData-portDischarge" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="name">Vessel</label>
                                            <input type="text" id="page-shipData-vessel" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Ship to Loc</label>
                                            <input type="text" id="page-shipData-shipToLoc" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Inco PTEO</label>
                                            <input type="text" id="page-shipData-incoPteo" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Inco EOS</label>
                                            <input type="text" id="page-shipData-incoEos" style="width: 100%;"
                                                    class="form-control col-sm-12"  readonly>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="name">Shipment Lot No</label>
                                            <div id="page-shipData-shipmentLotNo"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="name">Batch Allocation</label>
                                            <div id="page-shipData-batchAllocation"></div>
                                        </div>
                                    </div>
                                </div>
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
        var index_url   = "{{ route('shipmententry.index') }}";
        var post_url    = "{{ route('shipmententry.store') }}";
        var batch_show_url    = "{{ route('shipmententry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $page_shipData_customerCode       = '#page-shipData-customerCode';
        const $page_shipData_customerName       = '#page-shipData-customerName';
        const $page_shipData_poNumber           = '#page-shipData-poNumber';
        const $page_shipData_proInvoice         = '#page-shipData-proInvoice';
        const $page_shipData_containerNo        = '#page-shipData-containerNo';
        const $page_shipData_zbatch             = '#page-shipData-zbatch';
        const $page_shipData_netWeight          = '#page-shipData-netWeight';
        const $page_shipData_dateDepart         = '#page-shipData-dateDepart';
        const $page_shipData_portDischarge      = '#page-shipData-portDischarge';
        const $page_shipData_vessel             = '#page-shipData-vessel';
        const $page_shipData_shipToLoc          = '#page-shipData-shipToLoc';
        const $page_shipData_incoPteo           = '#page-shipData-incoPteo';
        const $page_shipData_incoEos            = '#page-shipData-incoEos';
        const $page_shipData_header             = '#modal-shipData-title';
        const $page_shipData_shipmentLotNo      = '#page-shipData-shipmentLotNo';
        const $page_shipData_batchAllocation    = '#page-shipData-batchAllocation';


    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populateShipmentPage($soNo, $soItem, $batchNo){
            $.ajax({
                url: batch_show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_datShipment',
                    batchNo: $batchNo,
                    soNo: $soNo,
                    soItem: $soItem,
                },
                success: function(response){
                    $($page_shipData_customerCode).val(response['data']['CUSTOMER_CODE']);
                    $($page_shipData_customerName).val(response['data']['CUSTOMER_NAME']);
                    $($page_shipData_zbatch).val(response['data']['ZBATCH']);
                    $($page_shipData_poNumber).val(response['data']['PO_NUM']);
                    $($page_shipData_proInvoice).val(response['data']['PRO_INVOICE']);
                    $($page_shipData_netWeight).val(response['data']['NET_WEIGHT'].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                    $($page_shipData_dateDepart).val(response['data']['DATE_DEPART']);
                    $($page_shipData_portDischarge).val(response['data']['PORT_DISCHARGE'].toUpperCase());
                    $($page_shipData_vessel).val(response['data']['VESSEL'].toUpperCase());
                    $($page_shipData_shipToLoc).val(response['data']['SHIP_TO_LOC'].toUpperCase());
                    $($page_shipData_incoPteo).val(response['data']['INCO_PTEO'].toUpperCase());
                    $($page_shipData_incoEos).val(response['data']['INCO_EOS'].toUpperCase());

                    /* populate container no */
                    let containerNo = response['data']['CONTAINER_NUMBER'].toUpperCase();
                    let containerNoValues = containerNo.split(";").map(v => v.trim()).filter(v => v);
                    let containerNoBadgesHtml = containerNoValues.map(v => `<span class="badge bg-primary text-white me-1">${v}</span>`).join("");
                    $($page_shipData_containerNo).html(containerNoBadgesHtml);

                    /* populate shipment lot no */
                    let shipLot = response['data']['SHIP_LOT'].toUpperCase();
                    let shipLotValues = shipLot.split(";").map(v => v.trim()).filter(v => v);
                    let shipLotBadgesHtml = shipLotValues.map(v => `<span class="badge bg-primary text-white me-1">${v}</span>`).join("");
                    $($page_shipData_shipmentLotNo).html(shipLotBadgesHtml);

                    /* populate batch allocation */
                    let batchAlloc = response['data']['BATCH_ALLOC'].toUpperCase();
                    let batchAllocValues = batchAlloc.split(";").map(v => v.trim()).filter(v => v);
                    let batchAllocBadgesHtml = batchAllocValues.map(v => `<span class="badge bg-primary text-white me-1">${v}</span>`).join("");
                    $($page_shipData_batchAllocation).html(batchAllocBadgesHtml);


                }
            });
        };

    /* FUNCTION DYNAMICS */


    /* FUNCTION INITIALIZATION */
        function initialize_shipData_modal($soNo=null, $batchNo=null){
            populate_emptyPage();

            let $separatedData = [$soNo];
            let $soItem = 'No Doc';

            if ($soNo.includes('-') == true) {
                let $separatedData = $soNo.split(/[-,]/);

                $soItem = $separatedData[1].toString();
                if (!$soItem.endsWith('0')) {
                    $soItem = $soItem.padStart(5, '0') + "0";
                } else {
                    $soItem = $soItem.padStart(5, '0');
                }
                $soNo = $separatedData[0];
                ajax_populateShipmentPage($soNo, $soItem, $batchNo);
            }

            $($page_shipData_header).html('SHIPMENT OVERVIEW (SO : ' + $soNo + ' - ' + $soItem + ' | BATCH : ' + $batchNo + ')');

        };
        function populate_emptyPage(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($page_shipData_customerCode).val('');
            $($page_shipData_customerName).val('');
            $($page_shipData_zbatch).val('');
            $($page_shipData_poNumber).val('');
            $($page_shipData_proInvoice).val('');
            $($page_shipData_containerNo).html('');
            $($page_shipData_netWeight).val('');
            $($page_shipData_dateDepart).val('');
            $($page_shipData_portDischarge).val('');
            $($page_shipData_vessel).val('');
            $($page_shipData_shipToLoc).val('');
            $($page_shipData_incoPteo).val('');
            $($page_shipData_incoEos).val('');
            $($page_shipData_shipmentLotNo).html('');
            $($page_shipData_batchAllocation).html('');

        };

    /* FUNCTION AUTO-REFRESH */



</script>
@endpush

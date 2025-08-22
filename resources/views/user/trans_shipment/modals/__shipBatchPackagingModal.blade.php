<div class="modal fade" data-backdrop="static" data-keyboard="false" style="z-index: 1041" tabindex="-1" role="dialog" id="modal-shipEntryBatch">
    <div class="modal-dialog" role="document" style="max-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="modal-shipEntryBatch-title">BATCH PACKAGING OVERVIEW</span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- header -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <input type="hidden" id="page-prdexecution-offset" class="form-control text-uppercase">
                                    <input type="hidden" id="page-prdexecution-totalbatch" class="form-control text-uppercase">
                                    <input type="hidden" id="page-prdexecution-id" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idpacking" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idlabel" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idpallet" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idproduct" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idtank" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idcsmark" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idsplabel" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-idcustomer" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-urllabel" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-urlsplabel" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-urlcsmark" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-urllabel-printed" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-urlsplabel-printed" class="form-control text-uppercase" >
                                    <input type="hidden" id="page-prdexecution-urlcsmark-printed" class="form-control text-uppercase" >
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Production Order (PO) No</label>
                                            <input type="text" id="page-prdexecution-order" style="width: 100%;"
                                                    class="form-control col-sm-12"  autocomplete="off"
                                                    oninput="this.value = this.value.toUpperCase();" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <label for="name">PO Long Text</label>
                                            <input type="text" id="page-prdexecution-longText" style="width: 100%;" class="form-control" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Production Date</label>
                                            <input type="date" id="page-prdexecution-date" style="width: 100%;" class="form-control" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Batch No *</label>
                                            <input type="text" id="page-prdexecution-batchno" style="width: 100%;"
                                                        class="form-control col-sm-12"  autocomplete="off"
                                                        oninput="this.value = this.value.toUpperCase();" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Customer</label>
                                            <input type="text" id="page-prdexecution-customer" style="width: 100%;"
                                                    class="form-control col-sm-12"  autocomplete="off"
                                                    oninput="this.value = this.value.toUpperCase();" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="name">Plan Qty Prd &nbsp; </label>
                                            <input type="number" id="page-prdexecution-qty" style="width: 100%;"
                                                class="form-control col-sm-12"  autocomplete="off" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="name" style="padding-top:8px;">Lot Size *</label>
                                            <input type="number" id="page-prdexecution-lot" style="width: 100%;"
                                                class="form-control col-sm-12"  autocomplete="off" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="name" style="padding-top:8px;">UoM</label>
                                            <input type="text" id="page-prdexecution-uom" style="width: 100%;"
                                                class="form-control col-sm-12"  autocomplete="off" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" style="padding-top:8px;">Process</label>
                                            <input type="text" id="page-prdexecution-process" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Packing Type</label>
                                            <input type="text" id="page-prdexecution-packing" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Spec</label>
                                            <input type="text" id="page-prdexecution-spec" style="width: 100%;"
                                                        class="form-control col-sm-12"  autocomplete="off"
                                                        oninput="this.value = this.value.toUpperCase();" readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Product</label>
                                            <input type="text" id="page-prdexecution-product" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Pallet Type</label>
                                            <input type="text" id="page-prdexecution-pallet" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Tank No *</label>
                                            <input type="text" id="page-prdexecution-tank" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="div-tankData">
                                        <div class="form-group">
                                            <label for="name">Tank Product | Volume (ton)</label>
                                            <input type="text" id="page-tankData" style="width: 100%;"
                                                    class="form-control col-sm-12" readonly>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-3" style="text-align:left; justify-content:flex-end">
                                        <div class="form-group">
                                            <label for="name" style="margin-top:10px"> Created By</label>
                                            <input type="text" id="page-prdexecution-createdBy" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <label for="name" style="margin-top:10px">Created Datetime</label>
                                            <input type="text" id="page-prdexecution-createdDate" style="width: 100%;" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name" style="margin-top:10px"> Approved By</label> &nbsp;
                                            <input type="text" id="page-prdexecution-approvedBy" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <label for="name" style="margin-top:10px">Approved Datetime</label>
                                            <input type="text" id="page-prdexecution-approvedDate" style="width: 100%;" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="page-prdexecution-deletedHeader">
                                        <div class="form-group">
                                            <label for="name" style="margin-top:10px"> Deleted By</label>
                                            <input type="text" id="page-prdexecution-deletedBy" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <label for="name" style="margin-top:10px">Deleted Datetime</label>
                                            <input type="text" id="page-prdexecution-deletedDate" style="width: 100%;" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="page-prdexecution-startedHeader">
                                        <div class="form-group">
                                            <label for="name" style="margin-top:10px"> Started By</label>
                                            <input type="text" id="page-prdexecution-startedBy" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <label for="name" style="margin-top:10px">Started Datetime</label>
                                            <input type="text" id="page-prdexecution-startedDate" style="width: 100%;" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3" id="page-prdexecution-finishedHeader">
                                        <div class="form-group">
                                            <label for="name" style="margin-top:10px"> Finished By</label> &nbsp;
                                            <input type="text" id="page-prdexecution-finishedBy" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <label for="name" style="margin-top:10px">Finished Datetime</label>
                                            <input type="text" id="page-prdexecution-finishedDate" style="width: 100%;" class="form-control" readonly>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="name">Main Label Type</label>
                                            <input type="text" id="page-prdexecution-label" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Main Label Preview</label>
                                            <div style="background-color: lightgray; height:250px">
                                                <img id="preview_label" src="#" alt="Preview Image" class="img-fluid" style="display: none; max-width:300px; height:250px; width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Printed Main Label</label> &nbsp
                                            <div style="background-color: lightgray; height:250px;">
                                                <img id="preview_label_printed" src="#" alt="Preview Image" class="img-fluid" style="display: none; max-width:300px; height:250px; width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group" id="page-prdexecution-csmark-div1">
                                            <label for="name">SHIPMARK 1 Type</label>
                                            <input type="text" id="page-prdexecution-csmark" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group" id="page-prdexecution-csmark-div2">
                                            <label for="name">SHIPMARK 1 Preview</label>
                                            <div style="background-color: lightgray; height:500px">
                                                <img id="preview_csmark" src="#" alt="Preview Image" style="display: none;max-width:300px; height:500px; width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group" id="page-prdexecution-csmark-div3">
                                            <label for="name">Printed SHIPMARK 1 </label> &nbsp
                                            <div style="background-color: lightgray; height:500px">
                                                <img id="preview_csmark_printed" src="#" alt="Preview Image" style="display: none;max-width:300px; height:500px; width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group" id="page-prdexecution-splabel-div1">
                                            <label for="name">SHIPMARK 2 Type</label>
                                            <input type="text" id="page-prdexecution-splabel" style="width: 100%;"
                                                        class="form-control col-sm-12"  readonly>
                                            <p class="text-danger"></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group" id="page-prdexecution-splabel-div2">
                                            <label for="name">SHIPMARK 2 Preview</label>
                                            <div style="background-color: lightgray; height:250px">
                                                <img id="preview_splabel" src="#" alt="Preview Image" class="img-fluid" style="display: none; max-width:300px; height:250px; width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group" id="page-prdexecution-splabel-div3">
                                            <label for="name">Printed SHIPMARK 2</label> &nbsp
                                            <div style="background-color: lightgray; height:250px">
                                                <img id="preview_splabel_printed" src="#" alt="Preview Image" class="img-fluid" style="display: none; max-width:300px; height:250px; width: 100%">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row" style="padding-left:20px" id="div-preparation">
                                    <div class="form-group">
                                        <label for="name">Preparation Records</label>
                                    </div>
                                    <div class="col-md-12" style="margin-top:-20px">
                                        <div class="table-responsive">
                                            <table class="table table-striped dataTable no-footer" id="dt-preparation" width="100%" role="grid" aria-describedby="table-1_info">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th>Preparation Type</th>
                                                        <th>Description</th>
                                                        <th>Status</th>
                                                        <th>Created By</th>
                                                        <th>Created At</th>
                                                        <th>Updated At</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
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
        const $dt_preparation                           = '#dt-preparation';
        const $header_start                             = '#page-prdexecution-startedHeader';
        const $page_prod_startedBy                      = '#page-prdexecution-startedBy';
        const $page_prod_startedDate                    = '#page-prdexecution-startedDate';

        const $plan_qty                                 = '#plan_qty';

        const $header_finish                            = '#page-prdexecution-finishedHeader';
        const $page_prod_finishedBy                     = '#page-prdexecution-finishedBy';
        const $page_prod_finishedDate                   = '#page-prdexecution-finishedDate';

        const $page_prod_tankdata                       = '#page-tankData';

        const $page_id                                  = '#page-prdexecution-id';
        const $page_prod_date                           = '#page-prdexecution-date';
        const $page_prod_order                          = '#page-prdexecution-order';
        const $page_prod_customer                       = '#page-prdexecution-customer';
        const $page_prod_spec                           = '#page-prdexecution-spec';
        const $page_prod_batchno                        = '#page-prdexecution-batchno';
        const $page_prod_prodqty                        = '#page-prdexecution-qty';
        const $page_prod_process                        = '#page-prdexecution-process';
        const $page_prod_packing                        = '#page-prdexecution-packing';
        const $page_prod_lot                            = '#page-prdexecution-lot';
        const $page_prod_product                        = '#page-prdexecution-product';
        const $page_prod_tank                           = '#page-prdexecution-tank';
        const $page_prod_pallet                         = '#page-prdexecution-pallet';
        const $page_prod_label                          = '#page-prdexecution-label';
        const $page_prod_uom                            = '#page-prdexecution-uom';
        const $page_prod_labelPreview                   = '#preview_label';
        const $page_prod_labelPreview_printed           = '#preview_label_printed';
        const $page_prod_customerMark                   = '#page-prdexecution-csmark';
        const $page_prod_customerMarkPreview            = '#preview_csmark';
        const $page_prod_customerMarkPreview_printed    = '#preview_csmark_printed';
        const $page_prod_specialLabel                   = '#page-prdexecution-splabel';
        const $page_prod_specialLabelPreview            = '#preview_splabel';
        const $page_prod_specialLabelPreview_printed    = '#preview_splabel_printed';

        const $page_prod_approvedBy                     = '#page-prdexecution-approvedBy';
        const $page_prod_approvedDate                   = '#page-prdexecution-approvedDate';
        const $page_prod_createdBy                      = '#page-prdexecution-createdBy';
        const $page_prod_createdDate                    = '#page-prdexecution-createdDate';

        const $page_prod_idpacking                      = '#page-prdexecution-idpacking';
        const $page_prod_idlabel                        = '#page-prdexecution-idlabel';
        const $page_prod_idpallet                       = '#page-prdexecution-idpallet';
        const $page_prod_idproduct                      = '#page-prdexecution-idproduct';
        const $page_prod_idtank                         = '#page-prdexecution-idtank';
        const $page_prod_idcsmark                       = '#page-prdexecution-idcsmark';
        const $page_prod_idsplabel                      = '#page-prdexecution-idsplabel';
        const $page_prod_idcustomer                     = '#page-prdexecution-idcustomer';

        const $page_prod_longText                       = '#page-prdexecution-longText';

        const $page_prod_spLabel_div1                   = '#page-prdexecution-splabel-div1';
        const $page_prod_spLabel_div2                   = '#page-prdexecution-splabel-div2';
        const $page_prod_spLabel_div3                   = '#page-prdexecution-splabel-div3';
        const $page_prod_csmark_div1                    = '#page-prdexecution-csmark-div1';
        const $page_prod_csmark_div2                    = '#page-prdexecution-csmark-div2';
        const $page_prod_csmark_div3                    = '#page-prdexecution-csmark-div3';

        const $page_prod_deletedHeader                  = '#page-prdexecution-deletedHeader';
        const $page_prod_deletedBy                      = '#page-prdexecution-deletedBy';
        const $page_prod_deletedDate                    = '#page-prdexecution-deletedDate';

        const $page_prod_offset                         = '#page-prdexecution-offset';
        const $page_prod_totalBatch                     = '#page-prdexecution-totalbatch';

        const $page_prod_urllabel                       = '#page-prdexecution-urllabel';
        const $page_prod_urlsplabel                     = '#page-prdexecution-urlsplabel';
        const $page_prod_urlcsmark                      = '#page-prdexecution-urlcsmark';
        const $page_prod_urllabel_printed               = '#page-prdexecution-urllabel-printed';
        const $page_prod_urlsplabel_printed             = '#page-prdexecution-urlsplabel-printed';
        const $page_prod_urlcsmark_printed              = '#page-prdexecution-urlcsmark-printed';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');

            /* EVENT LISTENER ON CHANGE */


            /* EVENT LISTENER ON CLICK */


        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_populatePage($batchNo){
            populate_emptyPage();

            $.ajax({
                url: batch_show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_shipmentBatchPackaging',
                    batchNo: $batchNo
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        populate_pageBatch(response);

                    }
                }
            });
        };
        function ajax_populatePreparationRecord($id, $batchNo){
            $($id).DataTable().destroy();
            $($id).DataTable({
                processing: true,
                serverSide: true,
                deferRender:true,
                searching: false,
                paging: false,
                ajax: {
                    url: batch_show_url,
                    data: {
                        flag: 'get_dtPreparationRecord',
                        batchNo: $batchNo
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
                },{
                    "targets": [3],
                    "render": function ( data, type, row ) {
                        if ( data == "1" ){
                            return '<i class="fa fa-check" style="color: green" title="Active"></i>';
                        } else if ( data == "0" ){
                            return '<i class="fa fa-times" style="color: red" title="Not Active"></i>';
                        }
                    },
                }],
                columns: [
                    { data: null, name: null, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'type', name: 'type', className: 'text-left', width:'8%'},
                    { data: 'description', name: 'description', className: 'text-left', width:'35%'},
                    { data: 'status', name: 'status', className: 'text-center'},
                    { data: 'created_by', name: 'created_by', className: 'text-left'},
                    { data: 'created_at', name: 'created_at', className: 'text-center'},
                    { data: 'updated_at', name: 'updated_at', className: 'text-center'},
                ]
            });
        };


    /* FUNCTION DYNAMICS */
        function populate_pageBatch(response){

            $($page_id).val(response['data'][0].id_prdexecution);
            $($page_prod_date).val(response['data'][0].entry_date);
            $($page_prod_order).val(response['data'][0].production_order);
            $($page_prod_customer).val(response['data'][0].customer);
            $($page_prod_spec).val(response['data'][0].spec);
            $($page_prod_batchno).val(response['data'][0].batch_no);
            $($page_prod_prodqty).val(response['data'][0].qty);
            $($page_prod_process).val(response['data'][0].process);
            $($page_prod_packing).val(response['data'][0].packing);
            $($page_prod_lot).val(response['data'][0].lot_qty);
            $($page_prod_product).val(response['data'][0].product);
            $($page_prod_tank).val(response['data'][0].tf_number);
            $($page_prod_pallet).val(response['data'][0].pallet);
            $($page_prod_label).val(response['data'][0].label);
            $($page_prod_uom).val(response['data'][0].uom);

            $($page_prod_approvedBy).val(response['data'][0].approved_by);
            $($page_prod_approvedDate).val(response['data'][0].approved_at);
            $($page_prod_createdBy).val(response['data'][0].created_by);
            $($page_prod_createdDate).val(response['data'][0].created_at);
            $($page_prod_finishedBy).val(response['data'][0].finished_by);
            $($page_prod_finishedDate).val(response['data'][0].finished_at);
            $($page_prod_startedBy).val(response['data'][0].started_by);
            $($page_prod_startedDate).val(response['data'][0].started_at);

            $($page_prod_idpacking).val(response['data'][0].id_packing);
            $($page_prod_idlabel).val(response['data'][0].id_label);
            $($page_prod_idpallet).val(response['data'][0].id_pallet);
            $($page_prod_idproduct).val(response['data'][0].id_product);
            $($page_prod_idtank).val(response['data'][0].id_tank);
            $($page_prod_idcsmark).val(response['data'][0].id_customer_mark);
            $($page_prod_idsplabel).val(response['data'][0].id_special_label);
            $($page_prod_idcustomer).val(response['data'][0].id_customer);
            $($page_prod_longText).val(response['data'][0].long_text);

            $($page_prod_label).val(response['data'][0].label);
            $($page_prod_customerMark).val(response['data'][0].csmark);
            $($page_prod_specialLabel).val(response['data'][0].splabel);

            $($page_prod_urllabel).val(response['data'][0].label_link);
            $($page_prod_urlsplabel).val(response['data'][0].splabel_link);
            $($page_prod_urlcsmark).val(response['data'][0].csmark_link);

            $($page_prod_tankdata).val(response['data'][0].tank_data);

            var $csmark = response['data'][0].csmark;
            var $splabel = response['data'][0].splabel;

            if ($csmark == null){
                $($page_prod_csmark_div1).prop('hidden', true);
                $($page_prod_csmark_div2).prop('hidden', true);
                $($page_prod_csmark_div3).prop('hidden', true);
            } else {
                $($page_prod_csmark_div1).prop('hidden', false);
                $($page_prod_csmark_div2).prop('hidden', false);
                $($page_prod_csmark_div3).prop('hidden', false);
            }

            if ($splabel == null){
                $('#page-prdexecution-splabel-div1').prop('hidden', true);
                $('#page-prdexecution-splabel-div2').prop('hidden', true);
                $('#page-prdexecution-splabel-div3').prop('hidden', true);
            } else {
                $('#page-prdexecution-splabel-div1').prop('hidden', false);
                $('#page-prdexecution-splabel-div2').prop('hidden', false);
                $('#page-prdexecution-splabel-div3').prop('hidden', false);
            }

            populate_csmarkPreview($page_prod_customerMarkPreview, response['data'][0].id_customer_mark);
            populate_splabelPreview($page_prod_specialLabelPreview, response['data'][0].id_special_label);
            populate_labelPreview($page_prod_labelPreview, response['data'][0].id_label);

            $($page_prod_labelPreview_printed).attr('src', "");
            $($page_prod_specialLabelPreview_printed).attr('src', "");
            $($page_prod_customerMarkPreview_printed).attr('src', "");

            var $imagePath = "{{ 'https://eoads.ecogreenoleo.com/oee-pph/public/printed_labels//:urlLink' }}";
            $imagePath  = $imagePath.replace(':urlLink', response['data'][0].p_label_link);
            $imagePath += '?timestamp=' + new Date().getTime();
            $($page_prod_urllabel_printed).val(response['data'][0].p_label_link);
            $($page_prod_labelPreview_printed).attr('src', $imagePath).show();

            if (response['data'][0].p_splabel_link !== ''){
                var $spimagePath = "{{ 'https://eoads.ecogreenoleo.com/oee-pph/public/printed_labels//:urlLink' }}";
                $spimagePath  = $spimagePath.replace(':urlLink', response['data'][0].p_splabel_link);
                $spimagePath += '?timestamp=' + new Date().getTime();
                $($page_prod_urlsplabel_printed).val(response['data'][0].p_splabel_link);
                $($page_prod_specialLabelPreview_printed).attr('src', $spimagePath).show();
            }
            if (response['data'][0].p_csmark_link !== ''){
                var $csimagePath = "{{ 'https://eoads.ecogreenoleo.com/oee-pph/public/printed_labels//:urlLink' }}";
                $csimagePath  = $csimagePath.replace(':urlLink', response['data'][0].p_csmark_link);
                $csimagePath += '?timestamp=' + new Date().getTime();
                $($page_prod_urlcsmark_printed).val(response['data'][0].p_csmark_link);
                $($page_prod_customerMarkPreview_printed).attr('src', $csimagePath).show();
            }

            if ($($page_prod_approvedBy).val() == ''){
                /* IF NOT APPROVE */
                    if (response['data'][0].status == 1){
                        $($page_prod_deletedHeader).prop('hidden', true);
                        $($page_prod_deletedBy).val('');
                        $($page_prod_deletedDate).val('');
                    } else {
                        $($page_prod_deletedHeader).prop('hidden', false);
                        $($page_prod_deletedBy).val(response['data'][0].updated_by);
                        $($page_prod_deletedDate).val(response['data'][0].updated_at);
                    }

                    $($header_finish).prop('hidden', true);
                    $($header_start).prop('hidden', true);

            } else {

                /* IF APPROVED */
                    $($page_prod_deletedHeader).prop('hidden', true);

                    if ($($page_prod_finishedBy).val() == ''){
                        /* IF NOT FINISHED */
                            if ($($page_prod_startedBy).val() == ''){
                                /* IF NOT STARTED */
                                    $($header_start).prop('hidden', true);
                                    $($header_finish).prop('hidden', true);
                            } else {
                                /* IF STARTED */
                                    $($header_start).prop('hidden', false);
                                    $($header_finish).prop('hidden', true);
                            }
                    } else {
                        /* IF FINISHED */
                            $($header_finish).prop('hidden', false);
                    }
            }

            /* POPULATE PREPARATION RECORDS */
            ajax_populatePreparationRecord($dt_preparation, $($page_prod_batchno).val());
        };
        function populate_csmarkPreview($tagId, selectedValue=null){
            $.ajax({
                url: batch_show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_csmark',
                    label: selectedValue
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $urlLink = encodeURIComponent(response['data'][0].url_link);
                        var $imagePath = "{{ 'https://eoads.ecogreenoleo.com/oee-pph/public/labels//:urlLink' }}";
                        $imagePath  = $imagePath.replace(':urlLink', $urlLink);
                        $($tagId).attr('src', $imagePath).show();
                    }
                },
                    error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                }
            });
        };
        function populate_splabelPreview($tagId, selectedValue=null){
            $.ajax({
                url: batch_show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_splabel',
                    label: selectedValue
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $urlLink = encodeURIComponent(response['data'][0].url_link);
                        var $imagePath = "{{ 'https://eoads.ecogreenoleo.com/oee-pph/public/labels//:urlLink' }}";
                        $imagePath  = $imagePath.replace(':urlLink', $urlLink);
                        $($tagId).attr('src', $imagePath).show();
                    }
                },
                    error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                }
            });
        };
        function populate_labelPreview($tagId, selectedValue=null){
            $.ajax({
                url: batch_show_url,
                type: 'get',
                dataType: 'json',
                data:{
                    flag: 'get_label',
                    label: selectedValue
                },
                success: function(response){
                    var len = 0;
                    if(response['data'] != null){
                        len = response['data'].length;
                    }
                    if(len > 0){
                        $urlLink = encodeURIComponent(response['data'][0].url_link);
                        var $imagePath = "{{ 'https://eoads.ecogreenoleo.com/oee-pph/public/labels//:urlLink' }}";
                        $imagePath  = $imagePath.replace(':urlLink', $urlLink);
                        $($tagId).attr('src', $imagePath).show();
                    }
                },
                    error: function(jqXHR, textStatus, errorThrown) {
                    console.error("AJAX Error:", textStatus, errorThrown);
                }
            });

        };


    /* FUNCTION INITIALIZATION */
        function initialize_shipEntryBatch_modal($batchNo=null){
            ajax_populatePage($batchNo.trim());
        };
        function populate_emptyPage(){
            var options = { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'Asia/Jakarta' };
            var currentDate = new Date().toLocaleDateString('fr-CA', options).split('/').join('');

            $($page_id).val('');
            $($page_prod_date).val('');
            $($page_prod_order).val('');
            $($page_prod_customer).val('');
            $($page_prod_spec).val('');
            $($page_prod_batchno).val('');
            $($page_prod_prodqty).val('');
            $($page_prod_process).val('');
            $($page_prod_packing).val('');
            $($page_prod_lot).val('');
            $($page_prod_uom).val('');
            $($page_prod_product).val('');
            $($page_prod_tank).val('');
            $($page_prod_pallet).val('');
            $($page_prod_label).val('');
            $($page_prod_customerMark).val('');
            $($page_prod_specialLabel).val('');
            $($page_prod_longText).val('');

            $($page_prod_labelPreview).attr('src', '#');
            $($page_prod_customerMarkPreview).attr('src', '#');
            $($page_prod_specialLabelPreview).attr('src', '#');

            $($page_prod_labelPreview_printed).attr('src', '#');
            $($page_prod_customerMarkPreview_printed).attr('src', '#');
            $($page_prod_specialLabelPreview_printed).attr('src', '#');

            $($page_prod_approvedBy).val('');
            $($page_prod_approvedDate).val('');
            $($page_prod_createdBy).val('');
            $($page_prod_createdDate).val('');

            $($page_prod_idpacking).val('');
            $($page_prod_idlabel).val('');
            $($page_prod_idpallet).val('');
            $($page_prod_idproduct).val('');
            $($page_prod_idtank).val('');
            $($page_prod_idcsmark).val('');
            $($page_prod_idsplabel).val('');
            $($page_prod_idcustomer).val('');

            $($page_prod_tankdata).val('');
        };

    /* FUNCTION AUTO-REFRESH */



</script>
@endpush


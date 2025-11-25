@extends('layouts.app_user')
@section('title', 'WIP Transaction')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row justify-content-left">
            <div class="col-md-5">
                <div class="card" style="background-color: black;">
                    <select name="type" id="filter-section" style="width: 100%;" class="form-control" required>
                        <option val='allSection'>- All Section -</option>
                        <option val='section101'>- Section 101/102 -</option>
                        <option val='section103'>- Section 103 -</option>
                        <option val='section104'>- Section 104 -</option>
                        <option val='section105'>- Section 105 -</option>
                        <option val='section106'>- Section 106/114 -</option>
                        <option val='section110'>- Section 110 -</option>
                        <option val='section111'>- Section 111/116 -</option>
                        <option val='section112'>- Section 112/114 -</option>
                        <option val='section302'>- Section 302 -</option>
                        <option val='routetogly'>- Section Route to Glycerine -</option>
                        <option val='routetogly'>- Section Route to WME -</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
			<div class="col-md-12">
                <!-- PROCESS OF SECTION 101 & 102 -->
                <div class="card" id="card-section101" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 101/102</b></a> &nbsp; &nbsp;
                        </div>
                        <!-- CPKO FEEDS -->
                        <div class="card">
                            <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">RM FEEDS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="cpko-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> RM Feed (101 FT0113) </button>
                                                    <button type="button" id="cpko-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="cpko-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF RM FEED (101 FT0113)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-cpko-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Feed Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Feed (MT)</th> -->
                                                                <!-- <th>Curr Feed (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>RM Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 101/102</b></a> &nbsp; &nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                        </div>
                        <!-- PKFAD + DA-OIL RUNDOWNS -->
                        <div class="card" style="background-color: #324031;">
                            <div class="card-body" style="margin-top:-10px; margin-bottom:-40px">
                                <!-- DA-OIL RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">DA-OIL RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="daoil-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> DA-OIL Rundown (102 FT0109) </button>
                                                    <button type="button" id="daoil-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="daoil-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF DA-OIL RUNDOWN (102 FT0109)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-daoil-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- PKFAD RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">PKFAD RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="pkfad-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> PKFAD Rundown (102 FT0129) </button>
                                                    <button type="button" id="pkfad-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="pkfad-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF PKFAD RUNDOWN (102 FT0129)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-pkfad-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 101/102</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 103 -->
                <div class="card" id="card-section103" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 103</b></a> &nbsp; &nbsp;
                        </div>
                        <!-- DA-OIL FEEDS -->
                        <div class="card">
                            <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100"
                                                        style="font-size:18px; white-space: normal; color:black;">DA-OIL FEEDS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="daoil-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> DA-OIL Feed (103 FT0101) </button>
                                                    <button type="button" id="daoil-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="daoil-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF DA-OIL FEED (103 FT0101)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-daoil-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Feed Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Feed (MT)</th> -->
                                                                <!-- <th>Curr Feed (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 103</b></a> &nbsp; &nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                        </div>
                        <!-- CRUDE-ME + TREATED-GLY RUNDOWNS -->
                        <div class="card" style="background-color: #324031;">
                            <div class="card-body" style="margin-top:-10px; margin-bottom:-40px">
                                <!-- CRUDE-ME RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">CRUDE-ME RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="crudeme-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CRUDE-ME Rundown (103 FT0329) </button>
                                                    <button type="button" id="crudeme-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="crudeme-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF CRUDE-ME RUNDOWN (103 FT0329)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-crudeme-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- TREATED-GLY RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">TREATED-GLYCERINE RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="treatedgly-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> TREATED-GLY Rundown (103 FT0266) </button>
                                                    <button type="button" id="treatedgly-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="treatedgly-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF TREATED-GLYCERINE RUNDOWN (103 FT0266)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-treatedgly-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>

                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 103</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 104 -->
                <div class="card" id="card-section104" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 104</b></a> &nbsp; &nbsp;
                        </div>
                        <!-- CRUDE-ME FEEDS -->
                        <div class="card">
                            <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100"
                                                        style="font-size:18px; white-space: normal; color:black;">CRUDE-ME FEEDS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="crudeme-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CRUDE-ME Feed (104 F0118) </button>
                                                    <button type="button" id="crudeme-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="crudeme-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF CRUDE-ME FEED (104 F0118)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-crudeme-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Feed Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Feed (MT)</th> -->
                                                                <!-- <th>Curr Feed (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 104</b></a> &nbsp; &nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                        </div>
                        <!-- ME60 + BDME + UME + ME28 RUNDOWNS -->
                        <div class="card" style="background-color: #324031;">
                            <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                <!-- UME RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">UME RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="ume-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> UME Rundown (104 F0110) </button>
                                                    <button type="button" id="ume-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="ume-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF UME RUNDOWN (104 F0110)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-ume-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- ME60 RUNDOWNS -->
                                <!-- <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">ME60 RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="me60-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ME60 Rundown (104 F0157) </button>
                                                    <button type="button" id="me60-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="me60-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF ME60 RUNDOWN (104 F0157)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-me60-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->
                                <!-- BDME RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">BDME RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="bdme-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> BDME Rundown (104 F0215) </button>
                                                    <button type="button" id="bdme-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="bdme-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF BDME RUNDOWN (104 F0215)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-bdme-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- ME28 RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">ME28 RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="me28-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ME28 Rundown (104 F0332) </button>
                                                    <button type="button" id="me28-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="me28-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF ME28 RUNDOWN (104 F0332)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-me28-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- ECONOATE6/65 RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">ECONOATE 6/65 RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="econoate665-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ECONOATE 6/65 Rundown (104 FO170) </button>
                                                    <button type="button" id="econoate665-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="econoate665-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF ECONOATE 6/65 RUNDOWN (104 FO170)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-econoate665-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- ME80 RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">ME80 RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="me80-104-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ME80 Rundown (104 FO157) </button>
                                                    <button type="button" id="me80-104-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="me80-104-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF ME80 RUNDOWN (104 FO157)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-me80-104-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 104</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 105 -->
                <div class="card" id="card-section105" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 105</b></a> &nbsp; &nbsp;
                        </div>
                        <div style="margin-bottom:10px">
                            <select name="type" id="filter-mode-105" style="width: 20%;" class="form-control" required>
                                <option val='mode-105-1'>- Mode LONG-CHAIN -</option>
                                <option val='mode-105-2'>- Mode SHORT-CHAIN -</option>
                            </select>
                            <a style="color:white">WARNING: DO NOT ENTRY SEVERAL MODES AT THE SAME TIME! ( MUST FINISH FEED & RUNDOWN ENTRY PER ONE MODE )</a>
                        </div>
                        <!-- DIV MODE-105-1 -->
                        <div id="div-mode-105-1">
                            <!-- ME28 FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">ME28 FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="me28-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ME28 Feed (105 FQ104) </button>
                                                        <button type="button" id="me28-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="me28-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF ME28 FEED (105 FQ104)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-me28-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 105</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                            <!-- CFA28 RUNDOWNS -->
                            <div class="card" style="background-color: #324031;">
                                <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                    <!-- CFA28 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA28 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="cfa28-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA28 Rundown (105 FQ808) </button>
                                                        <button type="button" id="cfa28-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="cfa28-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA28 RUNDOWN (105 FQ808)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-cfa28-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <!-- DIV MODE-105-2 -->
                        <div id="div-mode-105-2" style="display:none">
                            <!-- ME80 FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">ME80 FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="me80-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ME80 Feed (105 FQ104) </button>
                                                        <button type="button" id="me80-104-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="me80-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF ME80 FEED (105 FQ104)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-me80-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 105</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                            <!-- CFA80 RUNDOWNS -->
                            <div class="card" style="background-color: #324031;">
                                <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                    <!-- CFA80 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA80 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="cfa80-105-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA80 Rundown (105 FQ808) </button>
                                                        <button type="button" id="cfa80-105-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="cfa80-105-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA80 RUNDOWN (105 FQ808)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-cfa80-105-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <!-- END PROCESS 105 -->
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 105</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 106 -->
                <div class="card" id="card-section106" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 106/114</b></a> &nbsp; &nbsp;
                        </div>
                        <div style="margin-bottom:10px">
                            <select name="type" id="filter-mode-106" style="width: 20%;" class="form-control" required>
                                <option val='mode-106-1'>- Mode ECOROL 24 -</option>
                                <option val='mode-106-2'>- Mode ECOROL 12/14 -</option>
                            </select>
                            <a style="color:white">WARNING: DO NOT ENTRY SEVERAL MODES AT THE SAME TIME! ( MUST FINISH FEED & RUNDOWN ENTRY PER ONE MODE )</a>
                        </div>

                        <!-- CFA28 FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA28 FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="cfa28-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA28 Feed (106 F0115) </button>
                                                        <button type="button" id="cfa28-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="cfa28-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA28 FEED (106 F0115)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-cfa28-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 106/114</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                        <!-- RUNDOWNS -->
                            <!-- ECOROL-WAX + LEFA + FA24 + FA16 + FA18lrr RUNDOWNS -->
                                <div class="card" style="background-color: #324031;">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                        <!-- ECOROL-WAX RUNDOWNS -->
                                        <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                            <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <h4 class="card-header-title w-100">
                                                            <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                style="font-size:18px; white-space: normal; color:black;">ECOROL-WAX RUNDOWNS</span>
                                                        </h4>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div style="text-align:right; padding-bottom:10px">
                                                            <button type="button" id="ecorolwax-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ECOROL-WAX Rundown (106 F0245) </button>
                                                            <button type="button" id="ecorolwax-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                            <button type="button" id="ecorolwax-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                            <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                        </div>
                                                        <div style="text-align:left">LATEST LOG OF ECOROL-WAX RUNDOWN (106 F0245)</div>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped dataTable no-footer" id="table-ecorolwax-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                <thead>
                                                                    <tr>
                                                                        <th>WIP Trace No</th>
                                                                        <th>Entry Date</th>
                                                                        <th>Matl Doc</th>
                                                                        <!-- <th>Last Rundown (MT)</th> -->
                                                                        <!-- <th>Curr Rundown (MT)</th> -->
                                                                        <th>Total Material (MT)</th>
                                                                        <th>Total Supplier (MT)</th>
                                                                        <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                        <!-- LEFA RUNDOWNS -->
                                        <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                            <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <h4 class="card-header-title w-100">
                                                            <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                style="font-size:18px; white-space: normal; color:black;">LEFA RUNDOWNS</span>
                                                        </h4>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div style="text-align:right; padding-bottom:10px">
                                                            <button type="button" id="lefa-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> LEFA Rundown (106 F0167) </button>
                                                            <button type="button" id="lefa-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                            <button type="button" id="lefa-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                            <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                        </div>
                                                        <div style="text-align:left">LATEST LOG OF LEFA RUNDOWN (106 F0167)</div>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped dataTable no-footer" id="table-lefa-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                <thead>
                                                                    <tr>
                                                                        <th>WIP Trace No</th>
                                                                        <th>Entry Date</th>
                                                                        <th>Matl Doc</th>
                                                                        <!-- <th>Last Rundown (MT)</th> -->
                                                                        <!-- <th>Curr Rundown (MT)</th> -->
                                                                        <th>Total Material (MT)</th>
                                                                        <th>Total Supplier (MT)</th>
                                                                        <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                        <!-- DIV MODE-106-1 -->
                                        <div id="div-mode-106-1">
                                            <!-- FA24 RUNDOWNS -->
                                            <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                                <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                    <div class="row">
                                                        <div class="col-md-12 text-center">
                                                            <h4 class="card-header-title w-100">
                                                                <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                    style="font-size:18px; white-space: normal; color:black;">FA24 RUNDOWNS</span>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div style="text-align:right; padding-bottom:10px">
                                                                <button type="button" id="fa24-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA24 Rundown (106 F0134) </button>
                                                                <button type="button" id="fa24-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                                <button type="button" id="fa24-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                                <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                            </div>
                                                            <div style="text-align:left">LATEST LOG OF FA24 RUNDOWN (106 F0134)</div>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped dataTable no-footer" id="table-fa24-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>WIP Trace No</th>
                                                                            <th>Entry Date</th>
                                                                            <th>Matl Doc</th>
                                                                            <!-- <th>Last Rundown (MT)</th> -->
                                                                            <!-- <th>Curr Rundown (MT)</th> -->
                                                                            <th>Total Material (MT)</th>
                                                                            <th>Total Supplier (MT)</th>
                                                                            <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                            <!-- FA16 RUNDOWNS -->
                                            <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                                <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                    <div class="row">
                                                        <div class="col-md-12 text-center">
                                                            <h4 class="card-header-title w-100">
                                                                <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                    style="font-size:18px; white-space: normal; color:black;">FA16/99 RUNDOWNS</span>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div style="text-align:right; padding-bottom:10px">
                                                                <button type="button" id="fa16-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA16/99 Rundown (106 F0231) </button>
                                                                <button type="button" id="fa16-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                                <button type="button" id="fa16-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                                <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                            </div>
                                                            <div style="text-align:left">LATEST LOG OF FA16/99 RUNDOWN (106 F0231)</div>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped dataTable no-footer" id="table-fa16-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>WIP Trace No</th>
                                                                            <th>Entry Date</th>
                                                                            <th>Matl Doc</th>
                                                                            <!-- <th>Last Rundown (MT)</th> -->
                                                                            <!-- <th>Curr Rundown (MT)</th> -->
                                                                            <th>Total Material (MT)</th>
                                                                            <th>Total Supplier (MT)</th>
                                                                            <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                        <!-- DIV MODE-106-2 -->
                                        <div id="div-mode-106-2" style="display:none">
                                            <!-- FA12/99 RUNDOWNS -->
                                            <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                                <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                    <div class="row">
                                                        <div class="col-md-12 text-center">
                                                            <h4 class="card-header-title w-100">
                                                                <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                    style="font-size:18px; white-space: normal; color:black;">FA12/99 RUNDOWNS</span>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div style="text-align:right; padding-bottom:10px">
                                                                <button type="button" id="106-fa1299-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA12/99 Rundown (106 F0134) </button>
                                                                <button type="button" id="106-fa1299-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                                <button type="button" id="106-fa1299-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                                <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                            </div>
                                                            <div style="text-align:left">LATEST LOG OF FA12/99 RUNDOWN (106 F0134)</div>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped dataTable no-footer" id="table-106-fa1299-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>WIP Trace No</th>
                                                                            <th>Entry Date</th>
                                                                            <th>Matl Doc</th>
                                                                            <!-- <th>Last Rundown (MT)</th> -->
                                                                            <!-- <th>Curr Rundown (MT)</th> -->
                                                                            <th>Total Material (MT)</th>
                                                                            <th>Total Supplier (MT)</th>
                                                                            <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                            <!-- FA14/99 RUNDOWNS -->
                                            <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                                <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                    <div class="row">
                                                        <div class="col-md-12 text-center">
                                                            <h4 class="card-header-title w-100">
                                                                <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                    style="font-size:18px; white-space: normal; color:black;">FA14/99 RUNDOWNS</span>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div style="text-align:right; padding-bottom:10px">
                                                                <button type="button" id="106-fa1499-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA14/99 Rundown (106 F0231) </button>
                                                                <button type="button" id="106-fa1499-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                                <button type="button" id="106-fa1499-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                                <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                            </div>
                                                            <div style="text-align:left">LATEST LOG OF FA14/99 RUNDOWN (106 F0231)</div>
                                                            <div class="table-responsive">
                                                                <table class="table table-striped dataTable no-footer" id="table-106-fa1499-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>WIP Trace No</th>
                                                                            <th>Entry Date</th>
                                                                            <th>Matl Doc</th>
                                                                            <!-- <th>Last Rundown (MT)</th> -->
                                                                            <!-- <th>Curr Rundown (MT)</th> -->
                                                                            <th>Total Material (MT)</th>
                                                                            <th>Total Supplier (MT)</th>
                                                                            <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                        <!-- FA18lrr RUNDOWNS -->
                                        <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                            <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                                <div class="row">
                                                    <div class="col-md-12 text-center">
                                                        <h4 class="card-header-title w-100">
                                                            <span class="badge badge-light d-block w-100" id="card-1-header"
                                                                style="font-size:18px; white-space: normal; color:black;">FA18lrr RUNDOWNS</span>
                                                        </h4>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div style="text-align:right; padding-bottom:10px">
                                                            <button type="button" id="fa18lrr-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA18lrr Rundown (106 F0112) </button>
                                                            <button type="button" id="fa18lrr-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                            <button type="button" id="fa18lrr-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                            <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                        </div>
                                                        <div style="text-align:left">LATEST LOG OF FA18lrr RUNDOWN (106 F0112)</div>
                                                        <div class="table-responsive">
                                                            <table class="table table-striped dataTable no-footer" id="table-fa18lrr-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                                <thead>
                                                                    <tr>
                                                                        <th>WIP Trace No</th>
                                                                        <th>Entry Date</th>
                                                                        <th>Matl Doc</th>
                                                                        <!-- <th>Last Rundown (MT)</th> -->
                                                                        <!-- <th>Curr Rundown (MT)</th> -->
                                                                        <th>Total Material (MT)</th>
                                                                        <th>Total Supplier (MT)</th>
                                                                        <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <!-- END PROCESS 106/114 -->
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 106/114</b></a> &nbsp; &nbsp;
                            </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 110 -->
                <div class="card" id="card-section110" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 110</b></a> &nbsp; &nbsp;
                        </div>
                        <!-- TREATED-GLYCERINE FEEDS -->
                        <div class="card">
                            <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100"
                                                        style="font-size:18px; white-space: normal; color:black;">TREATED-GLYCERINE FEEDS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="treatedgly-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> TREATED-GLY Feed (110 F0107) </button>
                                                    <button type="button" id="treatedgly-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="treatedgly-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF TREATED-GLYCERINE FEED (110 F0107)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-treatedgly-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Feed Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Feed (MT)</th> -->
                                                                <!-- <th>Curr Feed (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 110</b></a> &nbsp; &nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                        </div>
                        <!-- CRUDE-GLYCERINE RUNDOWNS -->
                        <div class="card" style="background-color: #324031;">
                            <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                <!-- CRUDE-GLYCERINE RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">CRUDE-GLYCERINE RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="crudegly-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CRUDE-GLY Rundown (110 F0108) </button>
                                                    <button type="button" id="crudegly-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="crudegly-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF CRUDE-GLYCERINE RUNDOWN (110 F0108)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-crudegly-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 110</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 111/116 -->
                <div class="card" id="card-section111" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 111/116</b></a> &nbsp; &nbsp;
                        </div>
                        <!-- CRUDE-GLYCERINE FEEDS -->
                        <div class="card">
                            <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100"
                                                        style="font-size:18px; white-space: normal; color:black;">CRUDE-GLYCERINE FEEDS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="crudegly-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CRUDE-GLY Feed (111 F0118 + 116 FC01) </button>
                                                    <button type="button" id="crudegly-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="crudegly-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF CRUDE-GLYCERINE FEED (111 F0118)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-crudegly-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Feed Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Feed (MT)</th> -->
                                                                <!-- <th>Curr Feed (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 111/116</b></a> &nbsp; &nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                        </div>
                        <!-- GLYCERINE RUNDOWNS -->
                        <div class="card" style="background-color: #324031;">
                            <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                <!-- GLYCERINE RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">GLYCERINE RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="glycerine-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> GLYCERINE Rundown (111 FT0314 + 116 FT02) </button>
                                                    <button type="button" id="glycerine-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="glycerine-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF GLYCERINE RUNDOWN (111 FT0314 + 116 FT02)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-glycerine-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 111/116</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 112/114 -->
                <div class="card" id="card-section112" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 112/114</b></a> &nbsp; &nbsp;
                        </div>
                        <div style="margin-bottom:10px">
                            <select name="type" id="filter-mode" style="width: 20%;" class="form-control" required>
                                <option val='mode4'>- Mode FA18lrr -</option>
                                <option val='mode1'>- Mode FA24 -</option>
                                <option val='mode2'>- Mode FA14lrr -</option>
                                <option val='mode3'>- Mode Ecorol Wax -</option>
                            </select>
                            <a style="color:white">WARNING: DO NOT ENTRY SEVERAL MODES AT THE SAME TIME! ( MUST FINISH FEED & RUNDOWN ENTRY PER ONE MODE )</a>
                        </div>
                        <!-- DIV MODE-1 -->
                        <div id="div-mode1" style="display:none">
                            <!-- MATERIAL FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">FA24 FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa24-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA24 Feed (112 F0109) </button>
                                                        <button type="button" id="112-fa24-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa24-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA24 FEED (112 F0109)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-fa24-112-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 112/114</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                            <!-- MATERIAL RUNDOWNS -->
                            <div class="card" style="background-color: #324031;">
                                <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                    <!-- CFA28 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA28 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-cfa28-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA28 Rundown (112 F0139) </button>
                                                        <button type="button" id="112-cfa28-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-cfa28-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA28 RUNDOWN (112 F0139)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-cfa28-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- FA12/99 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">FA12/99 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa1299-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA12/99 Rundown (112 F0235) </button>
                                                        <button type="button" id="112-fa1299-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa1299-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA12/99 RUNDOWN (112 F0235)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa1299-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- FA14lrr RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">FA14lrr RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa14lrr-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA14lrr Rundown (112 F0224) </button>
                                                        <button type="button" id="112-fa14lrr-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa14lrr-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA14lrr RUNDOWN (112 F0224)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa14lrr-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <!-- DIV MODE-2 -->
                        <div id="div-mode2" style="display:none">
                            <!-- MATERIAL FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">FA14lrr FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa14lrr-m2-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA14lrr Feed (112 F0109) </button>
                                                        <button type="button" id="112-fa14lrr-m2-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa14lrr-m2-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA14lrr FEED (112 F0109)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa14lrr-m2-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 112/114</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                            <!-- MATERIAL RUNDOWNS -->
                            <div class="card" style="background-color: #324031;">
                                <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                    <!-- CFA28 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA28 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-cfa28-m2-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA28 Rundown (112 F0139 + 112 F0224) </button>
                                                        <button type="button" id="112-cfa28-m2-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-cfa28-m2-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA28 RUNDOWN (112 F0139 + 112 F0224)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-cfa28-m2-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- FA14/99 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">FA14/99 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa1499-m2-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA14/99 Rundown (112 F0235) </button>
                                                        <button type="button" id="112-fa1499-m2-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa1499-m2-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA14/99 RUNDOWN (112 F0235)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa1499-m2-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <!-- DIV MODE-3 -->
                        <div id="div-mode3" style="display:none">
                            <!-- MATERIAL FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">ECOROL WAX FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-ecowax-m3-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ECOROL WAX Feed (112 F0109) </button>
                                                        <button type="button" id="112-ecowax-m3-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-ecowax-m3-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF ECOROL WAX FEED (112 F0109)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-ecowax-m3-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 112/114</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                            <!-- MATERIAL RUNDOWNS -->
                            <div class="card" style="background-color: #324031;">
                                <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                    <!-- CFA28 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA28 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-cfa28-m3-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA28 Rundown (112 F0139) </button>
                                                        <button type="button" id="112-cfa28-m3-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-cfa28-m3-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA28 RUNDOWN (112 F0139)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-cfa28-m3-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- FA18lrr RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">FA18lrr RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa18lrr-m3-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA18lrr Rundown (112 F0235) </button>
                                                        <button type="button" id="112-fa18lrr-m3-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa18lrr-m3-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA18lrr RUNDOWN (112 F0235)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa18lrr-m3-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- ECOROL WAX RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">ECOROL WAX RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-ecowax-m3-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ECOROL WAX Rundown (112 F0224) </button>
                                                        <button type="button" id="112-ecowax-m3-rundown-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-ecowax-m3-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF ECOROL WAX RUNDOWN (112 F0224)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-ecowax-m3-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <!-- DIV MODE-4 -->
                        <div id="div-mode4" >
                            <!-- MATERIAL FEEDS -->
                            <div class="card">
                                <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                    <div class="card" style="background-color: #FFFFFF;">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100"
                                                            style="font-size:18px; white-space: normal; color:black;">FA18lrr FEEDS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa18lrr-m4-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA18lrr Feed (112 F0109) </button>
                                                        <button type="button" id="112-fa18lrr-m4-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa18lrr-m4-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA18lrr FEED (112 F0109)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa18lrr-m4-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>Feed Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Feed (MT)</th> -->
                                                                    <!-- <th>Curr Feed (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                            <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                                &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 112/114</b></a> &nbsp; &nbsp;
                                <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            </div>
                            <!-- MATERIAL RUNDOWNS -->
                            <div class="card" style="background-color: #324031;">
                                <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                    <!-- CFA28 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">CFA28 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-cfa28-m4-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> CFA28 Rundown (112 F0139) </button>
                                                        <button type="button" id="112-cfa28-m4-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-cfa28-m4-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF CFA28 RUNDOWN (112 F0139)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-cfa28-m4-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- FA18/99 RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">FA18/99 RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-fa1899-m4-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> FA18/99 Rundown (112 F0235) </button>
                                                        <button type="button" id="112-fa1899-m4-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-fa1899-m4-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF FA18/99 RUNDOWN (112 F0235)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-fa1899-m4-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                    <!-- ECOROL WAX RUNDOWNS -->
                                    <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                        <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <h4 class="card-header-title w-100">
                                                        <span class="badge badge-light d-block w-100" id="card-1-header"
                                                            style="font-size:18px; white-space: normal; color:black;">ECOROL WAX RUNDOWNS</span>
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div style="text-align:right; padding-bottom:10px">
                                                        <button type="button" id="112-ecowax-m4-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ECOROL WAX Rundown (112 F0224) </button>
                                                        <button type="button" id="112-ecowax-m4-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                        <button type="button" id="112-ecowax-m4-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                        <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                    </div>
                                                    <div style="text-align:left">LATEST LOG OF ECOROL WAX RUNDOWN (112 F0224)</div>
                                                    <div class="table-responsive">
                                                        <table class="table table-striped dataTable no-footer" id="table-112-ecowax-m4-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                            <thead>
                                                                <tr>
                                                                    <th>WIP Trace No</th>
                                                                    <th>Entry Date</th>
                                                                    <th>Matl Doc</th>
                                                                    <!-- <th>Last Rundown (MT)</th> -->
                                                                    <!-- <th>Curr Rundown (MT)</th> -->
                                                                    <th>Total Material (MT)</th>
                                                                    <th>Total Supplier (MT)</th>
                                                                    <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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

                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 112/114</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>

                <!-- PROCESS OF SECTION 302 -->
                <div class="card" id="card-section302" style="background-color: black;">
                    <div class="card-body" style="margin-bottom:-20px">
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>START OF SECTION 302</b></a> &nbsp; &nbsp;
                        </div>
                        <!-- UME FEEDS -->
                        <div class="card">
                            <div class="card-body" style="margin-top:-20px; margin-bottom:-40px">
                                <div class="card" style="background-color: #FFFFFF;">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100"
                                                        style="font-size:18px; white-space: normal; color:black;">UME FEEDS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="ume-feed-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> UME Feed (302 FT102) </button>
                                                    <button type="button" id="ume-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="ume-feed-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Feed Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF UME FEED (302 FT102)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-ume-feed" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>Feed Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Feed (MT)</th> -->
                                                                <!-- <th>Curr Feed (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>WIP Trace No./ Supplier / Batch SAP / Out_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>PROCESS OF SECTION 302</b></a> &nbsp; &nbsp;
                            <i class="fas fa-arrow-down" style="font-size: 24px; color:white"></i>
                        </div>
                        <!-- WME RUNDOWNS -->
                        <div class="card" style="background-color: #324031;">
                            <div class="card-body" style="margin-top:-10px; margin-bottom:-15px">
                                <!-- WME RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">WME RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="wme-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> WME Rundown (302 FT101) </button>
                                                    <button type="button" id="wme-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="wme-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF WME RUNDOWN (302 FT101)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-wme-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                                <!-- ME28-302 RUNDOWNS -->
                                <div class="card" style="background-color: #FFFFFF; margin-bottom:5px">
                                    <div class="card-body" style="margin-top:-10px; margin-bottom:-10px">
                                        <div class="row">
                                            <div class="col-md-12 text-center">
                                                <h4 class="card-header-title w-100">
                                                    <span class="badge badge-light d-block w-100" id="card-1-header"
                                                        style="font-size:18px; white-space: normal; color:black;">ME28-302 RUNDOWNS</span>
                                                </h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div style="text-align:right; padding-bottom:10px">
                                                    <button type="button" id="me28-302-rundown-material" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-edit" aria-hidden="true"></i> ME28-302 Rundown (302V04) </button>
                                                    <button type="button" id="me28-302-balance" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Balance Per Batches</button>
                                                    <button type="button" id="me28-302-rundown-log" class="btn btn-dark btn-sm" style="color:white"><i class="fa fa-bars" aria-hidden="true"></i> View Rundown Logs </button>
                                                    <button type="button" class="btn btn-danger btn-sm">QTY MATERIAL MUST TALLY WITH QTY SUPPLIER. CHECK AGAIN YOUR ENTRY. </button>
                                                </div>
                                                <div style="text-align:left">LATEST LOG OF ME28-302 RUNDOWN (302V04)</div>
                                                <div class="table-responsive">
                                                    <table class="table table-striped dataTable no-footer" id="table-me28-302-rundown" width="100%" role="grid" aria-describedby="table-1_info">
                                                        <thead>
                                                            <tr>
                                                                <th>WIP Trace No</th>
                                                                <th>Entry Date</th>
                                                                <th>Matl Doc</th>
                                                                <!-- <th>Last Rundown (MT)</th> -->
                                                                <!-- <th>Curr Rundown (MT)</th> -->
                                                                <th>Total Material (MT)</th>
                                                                <th>Total Supplier (MT)</th>
                                                                <th>Feed Trace No./ Supplier / Batch SAP / In_Qty (MT)</th>
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
                        <div style="display: flex; justify-content: center; align-items: center; margin-top:-15px; margin-bottom:10px">
                            &nbsp; &nbsp; <a style="font-size:18px; color:white"><b>END OF SECTION 302</b></a> &nbsp; &nbsp;
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
@push('js')
<!-- MODAL -->
    @include('user.trans_wip.modals.__balanceCpkoModal')
    @include('user.trans_wip.modals.__feedLogCpkoModal')

    @include('user.trans_wip.modals.__feedMaterialModal')
    @include('user.trans_wip.modals.__rundownMaterialModal')

    @include('user.trans_wip.modals.__balanceModal')
    @include('user.trans_wip.modals.__rundownLogModal')
    @include('user.trans_wip.modals.__feedLogModal')

    @include('user.trans_wip.modals.__addMaterialDocModal')

    @include('modals.__selectPlant')

<!-- SCRIPT -->
<script>
    /* VAR TOKEN + URL */
        var index_url   = "{{ route('wipentry.index') }}";
        var post_url    = "{{ route('wipentry.store') }}";
        var show_url    = "{{ route('wipentry.show', ':id') }}";

    /* VAR INDEX & PARAMETERIZATION */
        const $modal_addMaterialDoc         = '#modal-materialdoc-add';
        const $modal_feedMaterial           = '#modal-feed-material';
        const $modal_rundownMaterial        = '#modal-rundown-material';
        const $modal_balance                = '#modal-balance';
        const $modal_rundownLog             = '#modal-rundownlog';
        const $modal_feedLog                = '#modal-feedlog';

        const $btn_feed_addDocNo            = '#feed-addDocNo';
        const $btn_feed_editDocNo           = '#feed-editDocNo';
        const $btn_feed_cancel              = '#feed-cancel';
        const $btn_rundown_cancel           = '#rundown-cancel';

        const $cmb_filterSection            = '#filter-section';
        const $cmb_filterMode               = '#filter-mode';
        const $cmb_filterMode_106           = '#filter-mode-106';
        const $cmb_filterMode_105           = '#filter-mode-105';

        const $div_mode1                    = '#div-mode1';
        const $div_mode2                    = '#div-mode2';
        const $div_mode3                    = '#div-mode3';
        const $div_mode4                    = '#div-mode4';
        const $div_mode_106_1               = '#div-mode-106-1';
        const $div_mode_106_2               = '#div-mode-106-2';
        const $div_mode_105_1               = '#div-mode-105-1';
        const $div_mode_105_2               = '#div-mode-105-2';

        /* DATASET SECTION */
            const $card_section101              = '#card-section101';
            const $card_section103              = '#card-section103';
            const $card_section104              = '#card-section104';
            const $card_section105              = '#card-section105';
            const $card_section106              = '#card-section106';
            const $card_section110              = '#card-section110';
            const $card_section111              = '#card-section111';
            const $card_section302              = '#card-section302';
            const $card_section112              = '#card-section112';

        /* DATASET MATERIAL */
            const $rundownId_cpko               = '000'; /* GET RUNDOWN TO FEED TANK  */
            const $feedId_cpko                  = '001'; /* FEED ID FROM TANK TO WIP */
            const $rundownId_daoil              = '011';
            const $feedId_daoil                 = '002';
            const $rundownId_pkfad              = '021';
            const $feedId_pkfad                 = '-';
            const $rundownId_crudeme            = '012';
            const $feedId_crudeme               = '003';
            const $rundownId_treatedgly         = '022';
            const $feedId_treatedgly            = '004';
            const $rundownId_crudegly           = '014';
            const $feedId_crudegly              = '007';
            const $rundownId_glycerine          = '017';
            const $feedId_glycerine             = '-';
            const $rundownId_me60               = '013';
            const $feedId_me60                  = '-';
            const $rundownId_bdme               = '023';
            const $feedId_bdme                  = '-';
            const $rundownId_ume                = '033';
            const $feedId_ume                   = '005';
            const $rundownId_me28               = '043';
            const $feedId_me28                  = '006-01';
            const $rundownId_wme                = '015';
            const $rundownId_me28_302           = '025';
            const $feedId_wme                   = '-';
            const $rundownId_cfa28              = '016';
            const $feedId_cfa28                 = '008';
            const $rundownId_ecorolwax          = '018';
            const $feedId_ecorolwax             = '009';
            const $rundownId_lefa               = '028';
            const $feedId_lefa                  = '-';
            const $rundownId_fa24               = '038';
            const $feedId_fa24                  = '009';
            const $rundownId_fa16               = '048';
            const $feedId_fa16                  = '009';
            const $rundownId_fa18lrr            = '058';
            const $feedId_fa18lrr               = '009';
            const $rundownId_fa26               = '068';
            const $feedId_fa26                  = '-';
            const $feedId_112_fa24              = '009-01';
            const $feedId_112_fa14lrr           = '009-02';
            const $feedId_112_fa18lrr           = '009-03';
            const $feedId_112_ecowax            = '009-04';
            const $rundownId_112_ecowax         = '019';
            const $rundownId_112_fa18           = '029';
            const $rundownId_112_fa12           = '039';
            const $rundownId_112_fa18lrr        = '049';
            const $rundownId_112_fa14           = '059';
            const $rundownId_112_cfa28          = '069';
            const $rundownId_112_fa14lrr        = '079';
            const $rundownId_104_me80           = '063';
            const $feedId_105_me80              = '006-02';
            const $rundownId_105_cfa80          = '026';


            const $rundownId_106_fa1299         = '078';
            const $rundownId_106_fa1499         = '088';

            const $rundownId_econoate665        = '053';
            const $feedId_econoate665           = '-';


        /* DATASET VARIABLES FOR CPKO FEED */
            const $modal_cpkoBalance            = '#modal-cpko-balance';
            const $modal_cpkoFeedLog            = '#modal-cpko-feedlog';
            const $btn_cpkoBalance              = '#cpko-balance';
            const $btn_cpkoFeed_material        = '#cpko-feed-material';
            const $btn_cpkoFeed_log             = '#cpko-feed-log';
            const $dt_cpkoFeed                  = '#table-cpko-feed';

        /* DATASET VARIABLES FOR DA-OIL RUNDOWN + FEED */
            const $btn_daoilBalance             = '#daoil-balance';
            const $btn_daoilRundown_material    = '#daoil-rundown-material';
            const $btn_daoilRundown_log         = '#daoil-rundown-log';
            const $dt_daoilRundown              = '#table-daoil-rundown';

            const $btn_daoilFeed_material        = '#daoil-feed-material';
            const $btn_daoilFeed_log             = '#daoil-feed-log';
            const $dt_daoilFeed                  = '#table-daoil-feed';

        /* DATASET VARIABLES FOR PKFAD RUNDOWN */
            const $btn_pkfadBalance             = '#pkfad-balance';
            const $btn_pkfadRundown_material    = '#pkfad-rundown-material';
            const $btn_pkfadRundown_log         = '#pkfad-rundown-log';
            const $dt_pkfadRundown              = '#table-pkfad-rundown';

        /* DATASET VARIABLES FOR CRUDE-ME RUNDOWN + FEED */
            const $btn_crudemeBalance             = '#crudeme-balance';
            const $btn_crudemeRundown_material    = '#crudeme-rundown-material';
            const $btn_crudemeRundown_log         = '#crudeme-rundown-log';
            const $dt_crudemeRundown              = '#table-crudeme-rundown';

            const $btn_crudemeFeed_material        = '#crudeme-feed-material';
            const $btn_crudemeFeed_log             = '#crudeme-feed-log';
            const $dt_crudemeFeed                  = '#table-crudeme-feed';

        /* DATASET VARIABLES FOR TREATED-GLY RUNDOWN + FEED */
            const $btn_treatedglyBalance          = '#treatedgly-balance';
            const $btn_treatedglyRundown_material = '#treatedgly-rundown-material';
            const $btn_treatedglyRundown_log      = '#treatedgly-rundown-log';
            const $dt_treatedglyRundown           = '#table-treatedgly-rundown';

            const $btn_treatedglyFeed_material        = '#treatedgly-feed-material';
            const $btn_treatedglyFeed_log             = '#treatedgly-feed-log';
            const $dt_treatedglyFeed                  = '#table-treatedgly-feed';

        /* DATASET VARIABLES FOR CRUDE-GLY RUNDOWN + FEED */
            const $btn_crudeglyBalance             = '#crudegly-balance';
            const $btn_crudeglyRundown_material    = '#crudegly-rundown-material';
            const $btn_crudeglyRundown_log         = '#crudegly-rundown-log';
            const $dt_crudeglyRundown              = '#table-crudegly-rundown';

            const $btn_crudeglyFeed_material        = '#crudegly-feed-material';
            const $btn_crudeglyFeed_log             = '#crudegly-feed-log';
            const $dt_crudeglyFeed                  = '#table-crudegly-feed';

        /* DATASET VARIABLES FOR GLYCERINE RUNDOWN */
            const $btn_glycerineBalance             = '#glycerine-balance';
            const $btn_glycerineRundown_material    = '#glycerine-rundown-material';
            const $btn_glycerineRundown_log         = '#glycerine-rundown-log';
            const $dt_glycerineRundown              = '#table-glycerine-rundown';

        /* DATASET VARIABLES FOR ME60 RUNDOWN */
            const $btn_me60Balance             = '#me60-balance';
            const $btn_me60Rundown_material    = '#me60-rundown-material';
            const $btn_me60Rundown_log         = '#me60-rundown-log';
            const $dt_me60Rundown              = '#table-me60-rundown';

        /* DATASET VARIABLES FOR BDME RUNDOWN */
            const $btn_bdmeBalance             = '#bdme-balance';
            const $btn_bdmeRundown_material    = '#bdme-rundown-material';
            const $btn_bdmeRundown_log         = '#bdme-rundown-log';
            const $dt_bdmeRundown              = '#table-bdme-rundown';

        /* DATASET VARIABLES FOR UME RUNDOWN + FEED */
            const $btn_umeBalance             = '#ume-balance';
            const $btn_umeRundown_material    = '#ume-rundown-material';
            const $btn_umeRundown_log         = '#ume-rundown-log';
            const $dt_umeRundown              = '#table-ume-rundown';

            const $btn_umeFeed_material        = '#ume-feed-material';
            const $btn_umeFeed_log             = '#ume-feed-log';
            const $dt_umeFeed                  = '#table-ume-feed';

        /* DATASET VARIABLES FOR WME RUNDOWN */
            const $btn_wmeBalance             = '#wme-balance';
            const $btn_wmeRundown_material    = '#wme-rundown-material';
            const $btn_wmeRundown_log         = '#wme-rundown-log';
            const $dt_wmeRundown              = '#table-wme-rundown';

        /* DATASET VARIABLES FOR ME28 RUNDOWN + FEED */
            const $btn_me28Balance             = '#me28-balance';
            const $btn_me28Rundown_material    = '#me28-rundown-material';
            const $btn_me28Rundown_log         = '#me28-rundown-log';
            const $dt_me28Rundown              = '#table-me28-rundown';

            const $btn_me28_302Balance             = '#me28-302-balance';
            const $btn_me28_302Rundown_material    = '#me28-302-rundown-material';
            const $btn_me28_302Rundown_log         = '#me28-302-rundown-log';
            const $dt_me28_302Rundown              = '#table-me28-302-rundown';

            const $btn_me28Feed_material        = '#me28-feed-material';
            const $btn_me28Feed_log             = '#me28-feed-log';
            const $dt_me28Feed                  = '#table-me28-feed';

        /* DATASET VARIABLES FOR CFA28 RUNDOWN + FEED */
            const $btn_cfa28Balance             = '#cfa28-balance';
            const $btn_cfa28Rundown_material    = '#cfa28-rundown-material';
            const $btn_cfa28Rundown_log         = '#cfa28-rundown-log';
            const $dt_cfa28Rundown              = '#table-cfa28-rundown';

            const $btn_cfa28Feed_material        = '#cfa28-feed-material';
            const $btn_cfa28Feed_log             = '#cfa28-feed-log';
            const $dt_cfa28Feed                  = '#table-cfa28-feed';

        /* DATASET VARIABLES FOR LEFA RUNDOWN */
            const $btn_lefaBalance             = '#lefa-balance';
            const $btn_lefaRundown_material    = '#lefa-rundown-material';
            const $btn_lefaRundown_log         = '#lefa-rundown-log';
            const $dt_lefaRundown              = '#table-lefa-rundown';

        /* DATASET VARIABLES FOR ECOROL-WAX RUNDOWN + FEED */
            const $btn_ecorolwaxBalance             = '#ecorolwax-balance';
            const $btn_ecorolwaxRundown_material    = '#ecorolwax-rundown-material';
            const $btn_ecorolwaxRundown_log         = '#ecorolwax-rundown-log';
            const $dt_ecorolwaxRundown              = '#table-ecorolwax-rundown';

        /* DATASET VARIABLES FOR FA24 RUNDOWN + FEED */
            const $btn_fa24Balance             = '#fa24-balance';
            const $btn_fa24Rundown_material    = '#fa24-rundown-material';
            const $btn_fa24Rundown_log         = '#fa24-rundown-log';
            const $dt_fa24Rundown              = '#table-fa24-rundown';

        /* DATASET VARIABLES FOR FA16 RUNDOWN + FEED */
            const $btn_fa16Balance             = '#fa16-balance';
            const $btn_fa16Rundown_material    = '#fa16-rundown-material';
            const $btn_fa16Rundown_log         = '#fa16-rundown-log';
            const $dt_fa16Rundown              = '#table-fa16-rundown';

        /* DATASET VARIABLES FOR FA18lrr RUNDOWN + FEED */
            const $btn_fa18lrrBalance             = '#fa18lrr-balance';
            const $btn_fa18lrrRundown_material    = '#fa18lrr-rundown-material';
            const $btn_fa18lrrRundown_log         = '#fa18lrr-rundown-log';
            const $dt_fa18lrrRundown              = '#table-fa18lrr-rundown';
        /* DATASET VARIABLES FOR SECTION 112/114 RUNDOWN + FEED */
            /* MODE FA24 */
                const $btn_112fa24_Balance                  = '#112-fa24-balance';
                const $btn_112fa24_Feed_material            = '#112-fa24-feed-material';
                const $btn_112fa24_Feed_log                 = '#112-fa24-feed-log';
                const $dt_112fa24_Feed                      = '#table-fa24-112-feed';

                const $btn_112cfa28_Balance                 = '#112-cfa28-balance';
                const $btn_112cfa28_Rundown_material        = '#112-cfa28-rundown-material';
                const $btn_112cfa28_Rundown_log             = '#112-cfa28-rundown-log';
                const $dt_112cfa28_Rundown                  = '#table-112-cfa28-rundown';

                const $btn_112fa1299_Balance                = '#112-fa1299-balance';
                const $btn_112fa1299_Rundown_material       = '#112-fa1299-rundown-material';
                const $btn_112fa1299_Rundown_log            = '#112-fa1299-rundown-log';
                const $dt_112fa1299_Rundown                 = '#table-112-fa1299-rundown';

                const $btn_112fa14lrr_Balance               = '#112-fa14lrr-balance';
                const $btn_112fa14lrr_Rundown_material      = '#112-fa14lrr-rundown-material';
                const $btn_112fa14lrr_Rundown_log           = '#112-fa14lrr-rundown-log';
                const $dt_112fa14lrr_Rundown                = '#table-112-fa14lrr-rundown';
            /* MODE FA14lrr */
                const $btn_112fa14lrr_m2_Balance            = '#112-fa14lrr-m2-balance';
                const $btn_112fa14lrr_m2_Feed_material      = '#112-fa14lrr-m2-feed-material';
                const $btn_112fa14lrr_m2_Feed_log           = '#112-fa14lrr-m2-feed-log';
                const $dt_112fa14lrr_m2_Feed                = '#table-112-fa14lrr-m2-feed';

                const $btn_112fa1499_m2_Balance             = '#112-fa1499-m2-balance';
                const $btn_112fa1499_m2_Rundown_material    = '#112-fa1499-m2-rundown-material';
                const $btn_112fa1499_m2_Rundown_log         = '#112-fa1499-m2-rundown-log'
                const $dt_112fa1499_m2_Rundown              = '#table-112-fa1499-m2-rundown';

                const $btn_112cfa28_m2_Balance              = '#112-cfa28-m2-balance';
                const $btn_112cfa28_m2_Rundown_material     = '#112-cfa28-m2-rundown-material';
                const $btn_112cfa28_m2_Rundown_log          = '#112-cfa28-m2-rundown-log';
                const $dt_112cfa28_m2_Rundown               = '#table-112-cfa28-m2-rundown';
            /* MODE FA18lrr */
                const $btn_112fa18lrr_m4_Balance            = '#112-fa18lrr-m4-balance';
                const $btn_112fa18lrr_m4_Feed_material      = '#112-fa18lrr-m4-feed-material';
                const $btn_112fa18lrr_m4_Feed_log           = '#112-fa18lrr-m4-feed-log';
                const $dt_112fa18lrr_m4_Feed                = '#table-112-fa18lrr-m4-feed';

                const $btn_112fa1899_m4_Balance             = '#112-fa1899-m4-balance';
                const $btn_112fa1899_m4_Rundown_material    = '#112-fa1899-m4-rundown-material';
                const $btn_112fa1899_m4_Rundown_log         = '#112-fa1899-m4-rundown-log'
                const $dt_112fa1899_m4_Rundown              = '#table-112-fa1899-m4-rundown';

                const $btn_112cfa28_m4_Balance              = '#112-cfa28-m4-balance';
                const $btn_112cfa28_m4_Rundown_material     = '#112-cfa28-m4-rundown-material';
                const $btn_112cfa28_m4_Rundown_log          = '#112-cfa28-m4-rundown-log';
                const $dt_112cfa28_m4_Rundown               = '#table-112-cfa28-m4-rundown';

                const $btn_112ecowax_m4_Balance             = '#112-ecowax-m4-balance';
                const $btn_112ecowax_m4_Rundown_material    = '#112-ecowax-m4-rundown-material';
                const $btn_112ecowax_m4_Rundown_log         = '#112-ecowax-m4-rundown-log'
                const $dt_112ecowax_m4_Rundown              = '#table-112-ecowax-m4-rundown';

            /* MODE Ecorol-Wax */
                const $btn_112ecowax_m3_Balance             = '#112-ecowax-m3-balance';
                const $btn_112ecowax_m3_Feed_material       = '#112-ecowax-m3-feed-material';
                const $btn_112ecowax_m3_Feed_log            = '#112-ecowax-m3-feed-log';
                const $dt_112ecowax_m3_Feed                 = '#table-112-ecowax-m3-feed';

                const $btn_112fa18lrr_m3_Balance             = '#112-fa18lrr-m3-balance';
                const $btn_112fa18lrr_m3_Rundown_material    = '#112-fa18lrr-m3-rundown-material';
                const $btn_112fa18lrr_m3_Rundown_log         = '#112-fa18lrr-m3-rundown-log'
                const $dt_112fa18lrr_m3_Rundown              = '#table-112-fa18lrr-m3-rundown';

                const $btn_112cfa28_m3_Balance              = '#112-cfa28-m3-balance';
                const $btn_112cfa28_m3_Rundown_material     = '#112-cfa28-m3-rundown-material';
                const $btn_112cfa28_m3_Rundown_log          = '#112-cfa28-m3-rundown-log';
                const $dt_112cfa28_m3_Rundown               = '#table-112-cfa28-m3-rundown';

                const $btn_112ecowax_m3_Rundown_balance     = '#112-ecowax-m3-rundown-balance';
                const $btn_112ecowax_m3_Rundown_material    = '#112-ecowax-m3-rundown-material';
                const $btn_112ecowax_m3_Rundown_log         = '#112-ecowax-m3-rundown-log'
                const $dt_112ecowax_m3_Rundown              = '#table-112-ecowax-m3-rundown';
        /* DATASET VARIABLES FOR SECTION 106/114 RUNDOWN MODE ECOROL12/14 */
            const $btn_106_fa1299_m2_Balance            = '#106-fa1299-balance';
            const $btn_106_fa1299_m2_Rundown_material   = '#106-fa1299-rundown-material';
            const $btn_106_fa1299_m2_Rundown_log        = '#106-fa1299-rundown-log';
            const $dt_106_fa1299_m2_Rundown             = '#table-106-fa1299-rundown';

            const $btn_106_fa1499_m2_Balance            = '#106-fa1499-balance';
            const $btn_106_fa1499_m2_Rundown_material   = '#106-fa1499-rundown-material';
            const $btn_106_fa1499_m2_Rundown_log        = '#106-fa1499-rundown-log';
            const $dt_106_fa1499_m2_Rundown             = '#table-106-fa1499-rundown';
        /* DATASET VARIABLES FOR ECONOATE6/65 */
            const $btn_econoate665_Balance              = '#econoate665-balance';
            const $btn_econoate665_Rundown_material     = '#econoate665-rundown-material';
            const $btn_econoate665_Rundown_log          = '#econoate665-rundown-log';
            const $dt_econoate665_Rundown               = '#table-econoate665-rundown';
        /* DATASET VARIABLES FOR ME80 RUNDOWN + FEED */

            const $btn_me80_104Balance              = '#me80-104-balance';
            const $btn_me80_104Rundown_material     = '#me80-104-rundown-material';
            const $btn_me80_104Rundown_log          = '#me80-104-rundown-log';
            const $dt_me80_104Rundown               = '#table-me80-104-rundown';

            const $btn_me80Feed_material            = '#me80-feed-material';
            const $btn_me80Feed_log                 = '#me80-feed-log';
            const $dt_me80Feed                      = '#table-me80-feed';

        /* DATASET VARIABLES FOR CFA80 RUNDOWN */

            const $btn_cfa80_105Balance             = '#cfa80-105-balance';
            const $btn_cfa80_105Rundown_material    = '#cfa80-105-rundown-material';
            const $btn_cfa80_105Rundown_log         = '#cfa80-105-rundown-log';
            const $dt_cfa80_105Rundown              = '#table-cfa80-105-rundown';

    /* FUNCTION DOCUMENT READY */
        $(document).ready(function() {
            /* INITIALIZE */
                $('.modal').css('overflow-y', 'auto');
                initialize_page();

            // If admin/super-admin and no plant selected, show the modal
            @if(Auth::user()->hasRole(['admin', 'super-admin']) && empty($selectedPlant))
                $('#modal-selectPlant').modal('show');
            @endif

            $('#confirmPlantSelect').on('click', function() {
                var selectedPlant = $('#plantSelect').val();
                if (selectedPlant) {
                    window.location.href = "{{ route('wipentry.index') }}" + "?plant=" + selectedPlant;
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Please select a plant before continuing',
                    });
                }
            });

            /* EVENT LISTENER ON CHANGE */
                $(document).on('change', $cmb_filterSection, function(){
                    var $section = $($cmb_filterSection).val();

                    populate_hideAllSectionCard();
                    if ($section == '- All Section -'){
                        populate_showAllSectionCard();
                    } else if ($section == '- Section 101/102 -'){
                        $($card_section101).show();
                    } else if ($section == '- Section 103 -'){
                        $($card_section103).show();
                    } else if ($section == '- Section 110 -'){
                        $($card_section110).show();
                    } else if ($section == '- Section 111/116 -'){
                        $($card_section111).show();
                    } else if ($section == '- Section 104 -'){
                        $($card_section104).show();
                    } else if ($section == '- Section 105 -'){
                        $($card_section105).show();
                    } else if ($section == '- Section 106/114 -'){
                        $($card_section106).show();
                    } else if ($section == '- Section 302 -'){
                        $($card_section302).show();
                    } else if ($section == '- Section Route to Glycerine -'){
                        populate_routeToGlySectionCard();
                    } else if ($section == '- Section Route to WME -'){
                        populate_routeToWmeSectionCard();
                    } else if ($section == '- Section 112/114 -'){
                        $($card_section112).show();
                    }

                });

            /* EVENT LISTENER ON CLICK */
                $(document).on('click', $btn_feed_addDocNo, function(){
                    var $idTraceHead = $(this).attr('data-idTraceHead');

                    $($modal_addMaterialDoc).modal('show');
                    $($txt_materialdoc_flag).val('post_matlDocNumber');
                    $($txt_materialdoc_mode).val('ADD');
                    $($txt_materialdoc_id).val($idTraceHead);
                });
                $(document).on('click', $btn_feed_editDocNo, function(){
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $docNumber = $(this).attr('data-number');

                    $($modal_addMaterialDoc).modal('show');
                    $($txt_materialdoc_flag).val('post_matlDocNumber');
                    $($txt_materialdoc_mode).val('UPDATE');
                    $($txt_materialdoc_id).val($idTraceHead);
                    $($txt_materialdoc_number).val($docNumber);
                });
                $(document).on('click', $btn_feed_cancel, function(e){
                    e.preventDefault();
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $idBalanceHead = $(this).attr('data-idBalanceHead');
                    var $traceNo = $(this).attr('data-traceNo');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete trace no. ' + $traceNo,
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
                            ajax_postCancelFeed($idTraceHead, $idBalanceHead, $traceNo);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })

                });
                $(document).on('click', $btn_rundown_cancel, function(e){
                    e.preventDefault();
                    var $idTraceHead = $(this).attr('data-idTraceHead');
                    var $idBalanceHead = $(this).attr('data-idBalanceHead');
                    var $traceNo = $(this).attr('data-traceNo');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Delete trace no. ' + $traceNo,
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
                            ajax_postCancelRundown($idTraceHead, $idBalanceHead, $traceNo);
                        } else {
                            console.log(`data was dismissed by ${willDeleted.dismiss}`);
                        };
                    })
                });
                /* SECTION 101/102 */
                    /* FEED CPKO */
                        $(document).on('click', $btn_cpkoBalance, function(){
                            $($modal_cpkoBalance).modal('show');
                            initialize_cpkoBalanceModal('CPKO', $rundownId_cpko);
                        });
                        $(document).on('click', $btn_cpkoFeed_log, function(){
                            $($modal_cpkoFeedLog).modal('show');
                            initialize_cpkoFeedLogModal($feedId_cpko);
                        });
                        $(document).on('click', $btn_cpkoFeed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            initialize_feedMaterial('CPKO FEEDS ENTRY (101 FT0113)', 'ADD', 'post_materialFeed', $feedId_cpko, 'NON-CPKO', '101_FT0113');
                            //initialize_feedMaterial('CPKO FEEDS ENTRY (101 FT0113)', 'ADD', 'post_materialFeed', $feedId_cpko, null, null);
                        });
                    /* RUNDOWN DA-OIL */
                        $(document).on('click', $btn_daoilBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('DA-OIL', $rundownId_daoil);
                        });
                        $(document).on('click', $btn_daoilRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('DA-OIL', $rundownId_daoil);
                        });
                        $(document).on('click', $btn_daoilRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('DA-OIL RUNDOWN ENTRY (102 FT0109)', 'ADD', 'post_materialRundown', $rundownId_daoil, null, '102_FT0109');
                            //initialize_rundownMaterial('DA-OIL RUNDOWN ENTRY (101 FT0109)', 'ADD', 'post_materialRundown', $rundownId_daoil, null, null);
                        });
                    /* RUNDOWN PKFAD */
                        $(document).on('click', $btn_pkfadBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('PKFAD', $rundownId_pkfad);
                        });
                        $(document).on('click', $btn_pkfadRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('PKFAD', $rundownId_pkfad);
                        });
                        $(document).on('click', $btn_pkfadRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('PKFAD RUNDOWN ENTRY (102 FT0129)', 'ADD', 'post_materialRundown', $rundownId_pkfad, null, '102_FT0129');
                            //initialize_rundownMaterial('PKFAD RUNDOWN ENTRY (102 FT0129)', 'ADD', 'post_materialRundown', $rundownId_pkfad, null, null);
                        });
                /* SECTION 103 */
                    /* FEED DA-OIL */
                        $(document).on('click', $btn_daoilFeed_log, function(){
                            $($modal_feedLog).modal('show');
                            initialize_feedLogModal('DA-OIL', $feedId_daoil);
                        });
                        $(document).on('click', $btn_daoilFeed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            initialize_feedMaterial('DA-OIL FEED ENTRY (103 FT0101)', 'ADD', 'post_materialFeed', $feedId_daoil, 'NON-CPKO', '103_FT0101');
                            //initialize_feedMaterial('DA-OIL FEED ENTRY (103 FT0101)', 'ADD', 'post_materialFeed', $feedId_daoil, 'NON-CPKO');
                        });
                    /* RUNDOWN CRUDE-ME */
                        $(document).on('click', $btn_crudemeBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('CRUDE-ME', $rundownId_crudeme);
                        });
                        $(document).on('click', $btn_crudemeRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('CRUDE-ME', $rundownId_crudeme);
                        });
                        $(document).on('click', $btn_crudemeRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('CRUDE-ME RUNDOWN ENTRY (103 FT0329)', 'ADD', 'post_materialRundown', $rundownId_crudeme, null, '103_FT0329');
                            // initialize_rundownMaterial('CRUDE-ME RUNDOWN ENTRY (103 FT0329)', 'ADD', 'post_materialRundown', $rundownId_crudeme, null, null);
                        });
                    /* RUNDOWN TREATED-GLYCERINE */
                        $(document).on('click', $btn_treatedglyBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('TREATED-GLY', $rundownId_treatedgly);
                        });
                        $(document).on('click', $btn_treatedglyRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('TREATED-GLY', $rundownId_treatedgly);
                        });
                        $(document).on('click', $btn_treatedglyRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('TREATED-GLY RUNDOWN ENTRY (103 FT0266)', 'ADD', 'post_materialRundown', $rundownId_treatedgly, null, '103_FT0266');
                            // initialize_rundownMaterial('TREATED-GLY RUNDOWN ENTRY (103 FT0266)', 'ADD', 'post_materialRundown', $rundownId_treatedgly, null, null);
                        });
                /* SECTION 110 */
                    /* FEED TREATED-GLY */
                        $(document).on('click', $btn_treatedglyFeed_log, function(){
                            $($modal_feedLog).modal('show');
                            initialize_feedLogModal('TREATED-GLY', $feedId_treatedgly);
                        });
                        $(document).on('click', $btn_treatedglyFeed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            initialize_feedMaterial('TREATED-GLY FEED ENTRY (110 F0107)', 'ADD', 'post_materialFeed', $feedId_treatedgly, 'NON-CPKO', '110_F0107');
                            // initialize_feedMaterial('TREATED-GLY FEED ENTRY (110 F0107)', 'ADD', 'post_materialFeed', $feedId_treatedgly, 'NON-CPKO');
                        });
                    /* RUNDOWN CRUDE-GLY */
                        $(document).on('click', $btn_crudeglyBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('CRUDE-GLY', $rundownId_crudegly);
                        });
                        $(document).on('click', $btn_crudeglyRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('CRUDE-GLY', $rundownId_crudegly);
                        });
                        $(document).on('click', $btn_crudeglyRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('CRUDE-GLY RUNDOWN ENTRY (110 F0108)', 'ADD', 'post_materialRundown', $rundownId_crudegly, null, '110_F0108');
                            // initialize_rundownMaterial('CRUDE-GLY RUNDOWN ENTRY (110 F0108)', 'ADD', 'post_materialRundown', $rundownId_crudegly);
                        });
                /* SECTION 111/116 */
                    /* FEED CRUDE-GLY */
                        $(document).on('click', $btn_crudeglyFeed_log, function(){
                            $($modal_feedLog).modal('show');
                            initialize_feedLogModal('CRUDE-GLY', $feedId_crudegly);
                        });
                        $(document).on('click', $btn_crudeglyFeed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            initialize_feedMaterial('CRUDE-GLY FEED ENTRY (111 F0118 + 116 FC01)', 'ADD', 'post_materialFeed', $feedId_crudegly, 'NON-CPKO', '111_F0118_116_FC01');
                            //initialize_feedMaterial('CRUDE-GLY FEED ENTRY (111 F0118 + 116 FC01)', 'ADD', 'post_materialFeed', $feedId_crudegly, 'NON-CPKO');
                        });
                    /* RUNDOWN GLYCERINE */
                        $(document).on('click', $btn_glycerineBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('GLYCERINE', $rundownId_glycerine);
                        });
                        $(document).on('click', $btn_glycerineRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('GLYCERINE', $rundownId_glycerine);
                        });
                        $(document).on('click', $btn_glycerineRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('GLYCERINE RUNDOWN ENTRY (111 FT0314 + 116 FT02)', 'ADD', 'post_materialRundown', $rundownId_glycerine, null, '111_FT0314_116_FT02');
                            // initialize_rundownMaterial('GLYCERINE RUNDOWN ENTRY (111 FT0314)', 'ADD', 'post_materialRundown', $rundownId_glycerine);
                        });
                /* SECTION 104 */
                    /* FEED CRUDE-ME */
                        $(document).on('click', $btn_crudemeFeed_log, function(){
                            $($modal_feedLog).modal('show');
                            initialize_feedLogModal('CRUDE-ME', $feedId_crudeme);
                        });
                        $(document).on('click', $btn_crudemeFeed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            initialize_feedMaterial('CRUDE-ME FEED ENTRY (104 FT0118)', 'ADD', 'post_materialFeed', $feedId_crudeme, 'NON-CPKO', '104_FT0118');
                            // initialize_feedMaterial('CRUDE-ME FEED ENTRY (104 FT0118)', 'ADD', 'post_materialFeed', $feedId_crudeme, 'NON-CPKO');
                        });
                    /* RUNDOWN ME60 */
                        // $(document).on('click', $btn_me60Balance, function(){
                        //     $($modal_balance).modal('show');
                        //     initialize_balanceModal('ME60', $rundownId_me60);
                        // });
                        // $(document).on('click', $btn_me60Rundown_log, function(){
                        //     $($modal_rundownLog).modal('show');
                        //     initialize_rundownLogModal('ME60', $rundownId_me60);
                        // });
                        // $(document).on('click', $btn_me60Rundown_material, function(){
                        //     $($modal_rundownMaterial).modal('show');
                        //     initialize_rundownMaterial('ME60 RUNDOWN ENTRY (104 F0157)', 'ADD', 'post_materialRundown', $rundownId_me60);
                        // });
                    /* RUNDOWN BDME */
                        $(document).on('click', $btn_bdmeBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('BDME', $rundownId_bdme);
                        });
                        $(document).on('click', $btn_bdmeRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('BDME', $rundownId_bdme);
                        });
                        $(document).on('click', $btn_bdmeRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('BDME RUNDOWN ENTRY (104 F0215)', 'ADD', 'post_materialRundown', $rundownId_bdme, null, '104_F0215');
                            // initialize_rundownMaterial('BDME RUNDOWN ENTRY (104 F0215)', 'ADD', 'post_materialRundown', $rundownId_bdme);
                        });
                    /* RUNDOWN UME */
                        $(document).on('click', $btn_umeBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('UME', $rundownId_ume);
                        });
                        $(document).on('click', $btn_umeRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('UME', $rundownId_ume);
                        });
                        $(document).on('click', $btn_umeRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('UME RUNDOWN ENTRY (104 FT0110)', 'ADD', 'post_materialRundown', $rundownId_ume, null, '104_F0110');
                            // initialize_rundownMaterial('UME RUNDOWN ENTRY (104 FT0110)', 'ADD', 'post_materialRundown', $rundownId_ume);
                        });
                    /* RUNDOWN ME28 */
                        $(document).on('click', $btn_me28Balance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('ME28', $rundownId_me28);
                        });
                        $(document).on('click', $btn_me28Rundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('ME28', $rundownId_me28);
                        });
                        $(document).on('click', $btn_me28Rundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('ME28 RUNDOWN ENTRY (104 FT0332)', 'ADD', 'post_materialRundown', $rundownId_me28, null, '104_F0332');
                            //initialize_rundownMaterial('ME28 RUNDOWN ENTRY (104 FTO332)', 'ADD', 'post_materialRundown', $rundownId_me28);
                        });
                    /* RUNDOWN ECONOATE6/65 */
                        $(document).on('click', $btn_econoate665_Balance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('ECONOATE 6/65', $rundownId_econoate665);
                        });
                        $(document).on('click', $btn_econoate665_Rundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('ECONOATE 6/65', $rundownId_econoate665);
                        });
                        $(document).on('click', $btn_econoate665_Rundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('ECONOATE 6/65 RUNDOWN ENTRY (104 F0170)', 'ADD', 'post_materialRundown', $rundownId_econoate665, null, '104FT0170');
                            // initialize_rundownMaterial('ECONOATE 6/65 RUNDOWN ENTRY (104 F0170)', 'ADD', 'post_materialRundown', $rundownId_econoate665);
                        });
                    /* RUNDOWN ME80 */
                        $(document).on('click', $btn_me80_104Balance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('ME80', $rundownId_104_me80);
                        });
                        $(document).on('click', $btn_me80_104Rundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('ME80', $rundownId_104_me80);
                        });
                        $(document).on('click', $btn_me80_104Rundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            initialize_rundownMaterial('ME80 RUNDOWN ENTRY (104 F0157)', 'ADD', 'post_materialRundown', $rundownId_104_me80, null, '104_F0157');
                            // initialize_rundownMaterial('ME80 RUNDOWN ENTRY (104 F0157)', 'ADD', 'post_materialRundown', $rundownId_104_me80);
                        });
                /* SECTION 105 */
                    /* MODE */
                        $(document).on('change', $cmb_filterMode_105, function(){
                            $mode = $($cmb_filterMode_105).val();

                            $($div_mode_105_1).hide();
                            $($div_mode_105_2).hide();

                            if ($mode == '- Mode LONG-CHAIN -'){
                                $($div_mode_105_1).show();
                                ajax_dtFeed($dt_me28Feed, $feedId_me28);
                                ajax_dtRundown($dt_cfa28Rundown, $rundownId_cfa28);
                            } else if ($mode == '- Mode SHORT-CHAIN -'){
                                $($div_mode_105_2).show();
                                ajax_dtFeed($dt_me80Feed, $feedId_105_me80);
                                ajax_dtRundown($dt_cfa80_105Rundown, $rundownId_105_cfa80);
                            }
                        });
                    /* MODE LONG-CHAIN */
                        /* FEED ME28 */
                            $(document).on('click', $btn_me28Feed_log, function(){
                                $($modal_feedLog).modal('show');
                                initialize_feedLogModal('ME28', $feedId_me28);
                            });
                            $(document).on('click', $btn_me28Feed_material, function(){
                                $($modal_feedMaterial).modal('show');
                                initialize_feedMaterial('ME28 FEED ENTRY (105 FQ104)', 'ADD', 'post_materialFeed', $feedId_me28, 'NON-CPKO', '105_FQ104');
                                //initialize_feedMaterial('ME28 FEED ENTRY (105 FQ104)', 'ADD', 'post_materialFeed', $feedId_me28, 'NON-CPKO', null);
                            });
                        /* RUNDOWN CFA28 */
                            $(document).on('click', $btn_cfa28Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('CFA28', $rundownId_cfa28);
                            });
                            $(document).on('click', $btn_cfa28Rundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('CFA28', $rundownId_cfa28);
                            });
                            $(document).on('click', $btn_cfa28Rundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (105 FQ808)', 'ADD', 'post_materialRundown', $rundownId_cfa28, null, '105_FQ808');
                                //initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (105 FQ808)', 'ADD', 'post_materialRundown', $rundownId_cfa28, null, null);
                            });
                    /* MODE SHORT-CHAIN */
                        /* FEED ME80 */
                            $(document).on('click', $btn_me80Feed_log, function(){
                                $($modal_feedLog).modal('show');
                                initialize_feedLogModal('ME80', $feedId_105_me80);
                            });
                            $(document).on('click', $btn_me80Feed_material, function(){
                                $($modal_feedMaterial).modal('show');
                                initialize_feedMaterial('ME80 FEED ENTRY (105 FQ104)', 'ADD', 'post_materialFeed', $feedId_105_me80, 'NON-CPKO', '105_FQ104');
                                //initialize_feedMaterial('ME80 FEED ENTRY (105 FQ104)', 'ADD', 'post_materialFeed', $feedId_105_me80, 'NON-CPKO', null);
                            });
                        /* RUNDOWN CFA80 */
                            $(document).on('click', $btn_cfa80_105Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('CFA80', $rundownId_105_cfa80);
                            });
                            $(document).on('click', $btn_cfa80_105Rundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('CFA80', $rundownId_105_cfa80);
                            });
                            $(document).on('click', $btn_cfa80_105Rundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('CFA80 RUNDOWN ENTRY (105 FQ808)', 'ADD', 'post_materialRundown', $rundownId_105_cfa80, null, '105_FQ808');
                                //initialize_rundownMaterial('CFA80 RUNDOWN ENTRY (105 FQ808)', 'ADD', 'post_materialRundown', $rundownId_105_cfa80, null, null);
                            });
                /* SECTION 106 */
                    /* MODE */
                        $(document).on('change', $cmb_filterMode_106, function(){
                            $mode = $($cmb_filterMode_106).val();

                            $($div_mode_106_1).hide();
                            $($div_mode_106_2).hide();

                            ajax_dtFeed($dt_cfa28Feed, $feedId_cfa28);
                            ajax_dtRundown($dt_ecorolwaxRundown, $rundownId_ecorolwax);
                            ajax_dtRundown($dt_lefaRundown, $rundownId_lefa);
                            ajax_dtRundown($dt_fa18lrrRundown, $rundownId_fa18lrr);

                            if ($mode == '- Mode ECOROL 24 -'){
                                $($div_mode_106_1).show();
                                ajax_dtRundown($dt_fa24Rundown, $rundownId_fa24);
                                ajax_dtRundown($dt_fa16Rundown, $rundownId_fa16);
                            } else if ($mode == '- Mode ECOROL 12/14 -'){
                                $($div_mode_106_2).show();
                                ajax_dtRundown($dt_106_fa1299_m2_Rundown, $rundownId_106_fa1299);
                                ajax_dtRundown($dt_106_fa1499_m2_Rundown, $rundownId_106_fa1499);
                            }
                        });
                    /* FEED CFA28 */
                        $(document).on('click', $btn_cfa28Feed_log, function(){
                            $($modal_feedLog).modal('show');
                            initialize_feedLogModal('CFA28', $feedId_cfa28);
                        });
                        $(document).on('click', $btn_cfa28Feed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            initialize_feedMaterial('CFA28 FEED ENTRY (106 F0115)', 'ADD', 'post_materialFeed', $feedId_cfa28, 'NON-CPKO', '106_F0115');
                            // initialize_feedMaterial('CFA28 FEED ENTRY (106 F0115)', 'ADD', 'post_materialFeed', $feedId_cfa28, 'NON-CPKO');
                        });
                    /* MODE 106-1 */
                        /* RUNDOWN ECOROL-WAX */
                            $(document).on('click', $btn_ecorolwaxBalance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('ECOROL-WAX', $rundownId_ecorolwax);
                            });
                            $(document).on('click', $btn_ecorolwaxRundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('ECOROL-WAX', $rundownId_ecorolwax);
                            });
                            $(document).on('click', $btn_ecorolwaxRundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('ECOROL-WAX RUNDOWN ENTRY (106 F0245)', 'ADD', 'post_materialRundown', $rundownId_ecorolwax, null, '106_F0245');
                                //initialize_rundownMaterial('ECOROL-WAX RUNDOWN ENTRY (106 F0245)', 'ADD', 'post_materialRundown', $rundownId_ecorolwax);
                            });
                        /* RUNDOWN LEFA */
                            $(document).on('click', $btn_lefaBalance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('LEFA', $rundownId_lefa);
                            });
                            $(document).on('click', $btn_lefaRundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('LEFA', $rundownId_lefa);
                            });
                            $(document).on('click', $btn_lefaRundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('LEFA RUNDOWN ENTRY (106 F0167)', 'ADD', 'post_materialRundown', $rundownId_lefa, null, '106_F0167');
                                //initialize_rundownMaterial('LEFA RUNDOWN ENTRY (106 F0167)', 'ADD', 'post_materialRundown', $rundownId_lefa);
                            });
                        /* RUNDOWN FA24 */
                            $(document).on('click', $btn_fa24Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA24', $rundownId_fa24);
                            });
                            $(document).on('click', $btn_fa24Rundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('FA24', $rundownId_fa24);
                            });
                            $(document).on('click', $btn_fa24Rundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('FA24 RUNDOWN ENTRY (106 F0134)', 'ADD', 'post_materialRundown', $rundownId_fa24, null, '106_F0134');
                                // initialize_rundownMaterial('FA24 RUNDOWN ENTRY (106 F0134)', 'ADD', 'post_materialRundown', $rundownId_fa24);
                            });
                        /* RUNDOWN FA16/99 */
                            $(document).on('click', $btn_fa16Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA16/99', $rundownId_fa16);
                            });
                            $(document).on('click', $btn_fa16Rundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('FA16/99', $rundownId_fa16);
                            });
                            $(document).on('click', $btn_fa16Rundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('FA16/99 RUNDOWN ENTRY (106 F0231)', 'ADD', 'post_materialRundown', $rundownId_fa16, null, '106_F0231');
                                //initialize_rundownMaterial('FA16/99 RUNDOWN ENTRY (106 F0231)', 'ADD', 'post_materialRundown', $rundownId_fa16);
                            });
                        /* RUNDOWN FA18lrr */
                            $(document).on('click', $btn_fa18lrrBalance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA18lrr', $rundownId_fa18lrr);
                            });
                            $(document).on('click', $btn_fa18lrrRundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('FA18lrr', $rundownId_fa18lrr);
                            });
                            $(document).on('click', $btn_fa18lrrRundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('FA18lrr RUNDOWN ENTRY (106 F0112)', 'ADD', 'post_materialRundown', $rundownId_fa18lrr, null, '106_F0112');
                                // initialize_rundownMaterial('FA18lrr RUNDOWN ENTRY (106 F0112)', 'ADD', 'post_materialRundown', $rundownId_fa18lrr);
                            });
                    /* MODE 106-2 */
                        /* RUNDOWN FA12/99 */
                            $(document).on('click', $btn_106_fa1299_m2_Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA12/99', $rundownId_106_fa1299);
                            });
                            $(document).on('click', $btn_106_fa1299_m2_Rundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('FA12/99', $rundownId_106_fa1299);
                            });
                            $(document).on('click', $btn_106_fa1299_m2_Rundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('FA12/99 RUNDOWN ENTRY (106 F0134)', 'ADD', 'post_materialRundown', $rundownId_106_fa1299, null, '106_F0134');
                                // initialize_rundownMaterial('FA12/99 RUNDOWN ENTRY (106 F0134)', 'ADD', 'post_materialRundown', $rundownId_106_fa1299);
                            });
                        /* RUNDOWN FA14/99 */
                            $(document).on('click', $btn_106_fa1499_m2_Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA14/99', $rundownId_106_fa1499);
                            });
                            $(document).on('click', $btn_106_fa1499_m2_Rundown_log, function(){
                                $($modal_rundownLog).modal('show');
                                initialize_rundownLogModal('FA14/99', $rundownId_106_fa1499);
                            });
                            $(document).on('click', $btn_106_fa1499_m2_Rundown_material, function(){
                                $($modal_rundownMaterial).modal('show');
                                initialize_rundownMaterial('FA14/99 RUNDOWN ENTRY (106 F0231)', 'ADD', 'post_materialRundown', $rundownId_106_fa1499, null, '106_F0231');
                                // initialize_rundownMaterial('FA14/99 RUNDOWN ENTRY (106 F0231)', 'ADD', 'post_materialRundown', $rundownId_106_fa1499);
                            });
                /* SECTION 302 */
                    /* FEED UME */
                        $(document).on('click', $btn_umeFeed_log, function(){
                            $($modal_feedLog).modal('show');
                            initialize_feedLogModal('UME', $feedId_ume);
                        });
                        $(document).on('click', $btn_umeFeed_material, function(){
                            $($modal_feedMaterial).modal('show');
                            //initialize_feedMaterial('UME FEED ENTRY (302 FT102)', 'ADD', 'post_materialFeed', $feedId_ume, 'NON-CPKO', '302_FT102');
                            initialize_feedMaterial('UME FEED ENTRY (302 FT102)', 'ADD', 'post_materialFeed', $feedId_ume, 'NON-CPKO');
                        });
                    /* RUNDOWN WME */
                        $(document).on('click', $btn_wmeBalance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('WME', $rundownId_wme);
                        });
                        $(document).on('click', $btn_wmeRundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('WME', $rundownId_wme);
                        });
                        $(document).on('click', $btn_wmeRundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            // initialize_rundownMaterial('WME RUNDOWN ENTRY (302 FT101)', 'ADD', 'post_materialRundown', $rundownId_wme, null, '302_FT101');
                            initialize_rundownMaterial('WME RUNDOWN ENTRY (302 FT101)', 'ADD', 'post_materialRundown', $rundownId_wme);
                        });
                    /* RUNDOWN ME28-302 */
                        $(document).on('click', $btn_me28_302Balance, function(){
                            $($modal_balance).modal('show');
                            initialize_balanceModal('ME28-302', $rundownId_me28_302);
                        });
                        $(document).on('click', $btn_me28_302Rundown_log, function(){
                            $($modal_rundownLog).modal('show');
                            initialize_rundownLogModal('ME28-302', $rundownId_me28_302);
                        });
                        $(document).on('click', $btn_me28_302Rundown_material, function(){
                            $($modal_rundownMaterial).modal('show');
                            //initialize_rundownMaterial('ME28-302 RUNDOWN ENTRY (302V04)', 'ADD', 'post_materialRundown', $rundownId_me28_302, '302_V04');
                            initialize_rundownMaterial('ME28-302 RUNDOWN ENTRY (302V04)', 'ADD', 'post_materialRundown', $rundownId_me28_302);
                        });
                /* SECTION 112 */
                    /* MODE */
                        $(document).on('change', $cmb_filterMode, function(){
                            $mode = $($cmb_filterMode).val();

                            $($div_mode1).hide();
                            $($div_mode2).hide();
                            $($div_mode3).hide();
                            $($div_mode4).hide();

                            if ($mode == '- Mode FA24 -'){
                                $($div_mode1).show();
                                ajax_dtFeed($dt_112fa24_Feed, $feedId_112_fa24);
                                ajax_dtRundown($dt_112cfa28_Rundown, $rundownId_112_cfa28);
                            } else if ($mode == '- Mode FA14lrr -'){
                                $($div_mode2).show();
                                ajax_dtFeed($dt_112fa14lrr_m2_Feed, $feedId_112_fa14lrr);
                                ajax_dtRundown($dt_112cfa28_m2_Rundown, $rundownId_112_cfa28);
                                ajax_dtRundown($dt_112fa1499_m2_Rundown, $rundownId_112_fa14);
                            } else if ($mode == '- Mode FA18lrr -'){
                                $($div_mode4).show();
                                ajax_dtFeed($dt_112fa18lrr_m4_Feed, $feedId_112_fa18lrr);
                                ajax_dtRundown($dt_112cfa28_m4_Rundown, $rundownId_112_cfa28);
                                ajax_dtRundown($dt_112fa1899_m4_Rundown, $rundownId_112_fa18);
                                ajax_dtRundown($dt_112ecowax_m4_Rundown, $rundownId_112_ecowax);
                            } else if ($mode == '- Mode Ecorol Wax -'){
                                $($div_mode3).show();
                                ajax_dtFeed($dt_112ecowax_m3_Feed, $feedId_112_ecowax);
                                ajax_dtRundown($dt_112cfa28_m3_Rundown, $rundownId_112_cfa28);
                                ajax_dtRundown($dt_112fa18lrr_m3_Rundown, $rundownId_112_fa18lrr);
                                ajax_dtRundown($dt_112ecowax_m3_Rundown, $rundownId_112_ecowax);
                            }
                        });
                    /* MODE FA24 */
                        /* FEED Mode FA24 */
                            $(document).on('click', $btn_112fa24_Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA24', $rundownId_fa24);
                            });
                            $(document).on('click', $btn_112fa24_Feed_log, function(){
                                $($modal_feedLog).modal('show');
                                initialize_feedLogModal('SECT 112 - FA24', $feedId_112_fa24);
                            });
                            $(document).on('click', $btn_112fa24_Feed_material, function(){
                                $($modal_feedMaterial).modal('show');
                                initialize_feedMaterial('MODE FA24 FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_fa24, 'NON-CPKO', '112_F0109');
                                // initialize_feedMaterial('MODE FA24 FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_fa24, 'NON-CPKO');
                            });

                        /* RUNDOWN Mode FA24 */
                            /* CFA28 */
                                $(document).on('click', $btn_112cfa28_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28, null, '112_F0139');
                                    // initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28);
                                });
                            /* FA12/99 */
                                $(document).on('click', $btn_112fa1299_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('FA12/99', $rundownId_112_fa12);
                                });
                                $(document).on('click', $btn_112fa1299_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('FA12/99', $rundownId_112_fa12);
                                });
                                $(document).on('click', $btn_112fa1299_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('FA12/99 RUNDOWN ENTRY (112 F0235)', 'ADD', 'post_materialRundown', $rundownId_112_fa12, null, '112_F0235');
                                    // initialize_rundownMaterial('FA12/99 RUNDOWN ENTRY (112 F0235)', 'ADD', 'post_materialRundown', $rundownId_112_fa12);
                                });
                            /* FA14lrr */
                                $(document).on('click', $btn_112fa14lrr_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('FA14lrr', $rundownId_112_fa14lrr);
                                });
                                $(document).on('click', $btn_112fa14lrr_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('FA14lrr', $rundownId_112_fa14lrr);
                                });
                                $(document).on('click', $btn_112fa14lrr_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('FA14lrr RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_fa14lrr, null, '112_F0224');
                                    //initialize_rundownMaterial('FA14lrr RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_fa14lrr);
                                });
                    /* MODE FA14lrr */
                        /* FEED Mode FA14lrr */
                            $(document).on('click', $btn_112fa14lrr_m2_Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA14lrr', $rundownId_112_fa14lrr);
                            });
                            $(document).on('click', $btn_112fa14lrr_m2_Feed_log, function(){
                                $($modal_feedLog).modal('show');
                                initialize_feedLogModal('SECT 112 - FA14lrr', $feedId_112_fa14lrr);
                            });
                            $(document).on('click', $btn_112fa14lrr_m2_Feed_material, function(){
                                $($modal_feedMaterial).modal('show');
                                initialize_feedMaterial('MODE FA14lrr FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_fa14lrr, 'NON-CPKO', '112_F0109');
                                // initialize_feedMaterial('MODE FA14lrr FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_fa14lrr, 'NON-CPKO');
                            });
                        /* RUNDOWN Mode FA14lrr */
                            /* CFA28 */
                                $(document).on('click', $btn_112cfa28_m2_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_m2_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_m2_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139 + 112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28, null, '112_F0139_112_F0224');
                                    //initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139 + 112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28);
                                });
                            /* FA14/99 */
                                $(document).on('click', $btn_112fa1499_m2_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('FA14/99', $rundownId_112_fa14);
                                });
                                $(document).on('click', $btn_112fa1499_m2_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('FA14/99', $rundownId_112_fa14);
                                });
                                $(document).on('click', $btn_112fa1499_m2_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('FA14/99 RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_fa14, null, '112_F0224');
                                    //initialize_rundownMaterial('FA14/99 RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_fa14);
                                });
                    /* MODE FA18lrr */
                        /* FEED Mode FA18lrr */
                            $(document).on('click', $btn_112fa18lrr_m4_Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('FA18lrr', $rundownId_112_fa18lrr);
                            });
                            $(document).on('click', $btn_112fa18lrr_m4_Feed_log, function(){
                                $($modal_feedLog).modal('show');
                                initialize_feedLogModal('SECT 112 - FA18lrr', $feedId_112_fa18lrr);
                            });
                            $(document).on('click', $btn_112fa18lrr_m4_Feed_material, function(){
                                $($modal_feedMaterial).modal('show');
                                initialize_feedMaterial('MODE FA18lrr FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_fa18lrr, 'NON-CPKO', '112_F0109');
                                //initialize_feedMaterial('MODE FA18lrr FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_fa18lrr, 'NON-CPKO');
                            });
                        /* RUNDOWN Mode FA18lrr */
                            /* CFA28 */
                                $(document).on('click', $btn_112cfa28_m4_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_m4_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_m4_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28, null, '112_F0139');
                                    //initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28);
                                });
                            /* FA18/99 */
                                $(document).on('click', $btn_112fa1899_m4_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('FA18/99', $rundownId_112_fa18);
                                });
                                $(document).on('click', $btn_112fa1899_m4_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('FA18/99', $rundownId_112_fa18);
                                });
                                $(document).on('click', $btn_112fa1899_m4_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('FA18/99 RUNDOWN ENTRY (112 F0235)', 'ADD', 'post_materialRundown', $rundownId_112_fa18, null, '112_F0235');
                                    // initialize_rundownMaterial('FA18/99 RUNDOWN ENTRY (112 F0235)', 'ADD', 'post_materialRundown', $rundownId_112_fa18);
                                });
                            /* EOCOROL WAX */
                                $(document).on('click', $btn_112ecowax_m4_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('ECOROL WAX', $rundownId_112_ecowax);
                                });
                                $(document).on('click', $btn_112ecowax_m4_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('ECOROL WAX', $rundownId_112_ecowax);
                                });
                                $(document).on('click', $btn_112ecowax_m4_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('ECOROL WAX RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_ecowax, null, '112_F0224');
                                    // initialize_rundownMaterial('ECOROL WAX RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_ecowax);
                                });
                    /* MODE ECOROL WAX */
                        /* FEED Mode ECOROL WAX */
                            $(document).on('click', $btn_112ecowax_m3_Balance, function(){
                                $($modal_balance).modal('show');
                                initialize_balanceModal('ECOROL WAX', $rundownId_ecorolwax);
                            });
                            $(document).on('click', $btn_112ecowax_m3_Feed_log, function(){
                                $($modal_feedLog).modal('show');
                                initialize_feedLogModal('SECT 112 - ECOROL WAX', $feedId_112_ecowax);
                            });
                            $(document).on('click', $btn_112ecowax_m3_Feed_material, function(){
                                $($modal_feedMaterial).modal('show');
                                initialize_feedMaterial('MODE ECOROL WAX FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_ecowax, 'NON-CPKO', '112_F0109');
                                // initialize_feedMaterial('MODE ECOROL WAX FEED ENTRY (112 F0109)', 'ADD', 'post_materialFeed', $feedId_112_ecowax, 'NON-CPKO');
                            });
                        /* RUNDOWN Mode ECOROL WAX */
                            /* CFA28 */
                                $(document).on('click', $btn_112cfa28_m3_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_m3_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('CFA28', $rundownId_112_cfa28);
                                });
                                $(document).on('click', $btn_112cfa28_m3_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28, null, '112_F0139');
                                    // initialize_rundownMaterial('CFA28 RUNDOWN ENTRY (112 F0139)', 'ADD', 'post_materialRundown', $rundownId_112_cfa28);
                                });
                            /* FA18lrr */
                                $(document).on('click', $btn_112fa18lrr_m3_Balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('FA18lrr', $rundownId_112_fa18lrr);
                                });
                                $(document).on('click', $btn_112fa18lrr_m3_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('FA18lrr', $rundownId_112_fa18lrr);
                                });
                                $(document).on('click', $btn_112fa18lrr_m3_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('FA18lrr RUNDOWN ENTRY (112 F0235)', 'ADD', 'post_materialRundown', $rundownId_112_fa18lrr, null, '112_F0235');
                                    // initialize_rundownMaterial('FA18lrr RUNDOWN ENTRY (112 F0235)', 'ADD', 'post_materialRundown', $rundownId_112_fa18lrr);
                                });
                            /* EOCOROL WAX */
                                $(document).on('click', $btn_112ecowax_m3_Rundown_balance, function(){
                                    $($modal_balance).modal('show');
                                    initialize_balanceModal('ECOROL WAX', $rundownId_112_ecowax);
                                });
                                $(document).on('click', $btn_112ecowax_m3_Rundown_log, function(){
                                    $($modal_rundownLog).modal('show');
                                    initialize_rundownLogModal('ECOROL WAX', $rundownId_112_ecowax);
                                });
                                $(document).on('click', $btn_112ecowax_m3_Rundown_material, function(){
                                    $($modal_rundownMaterial).modal('show');
                                    initialize_rundownMaterial('ECOROL WAX RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_ecowax, null, '112_F0224');
                                    // initialize_rundownMaterial('ECOROL WAX RUNDOWN ENTRY (112 F0224)', 'ADD', 'post_materialRundown', $rundownId_112_ecowax);
                                });
            /* LISTENER ON MODAL STACK */
                $($modal_addMaterialDoc).on('show.bs.modal', function () {
                    if ( $($btn_cpkoFeed_log).hasClass('show') ) {
                        $($btn_cpkoFeed_log).css('opacity', 0.3);
                    }
                    if ( $($modal_rundownLog).hasClass('show') ) {
                        $($modal_rundownLog).css('opacity', 0.3);
                    }
                });
                $($modal_addMaterialDoc).on('hidden.bs.modal', function () {
                    if ( $($btn_cpkoFeed_log).hasClass('show') ) {
                        $($btn_cpkoFeed_log).css('opacity', 1);
                    }
                    if ( $($modal_rundownLog).hasClass('show') ) {
                        $($modal_rundownLog).css('opacity', 1);
                    }
                });

        });

    /* FUNCTION SELECT2 / DROPDOWN */


    /* FUNCTION AJAX */
        function ajax_postCancelFeed($idTraceHead, $idBalanceHead, $traceNo){
            $.ajax({
                url: post_url,
                method: "POST",
                dataType: "JSON",
                data:{
                    flag: 'post_cancelFeed',
                    idTraceHead: $idTraceHead,
                    idBalanceHead: $idBalanceHead,
                    traceNo: $traceNo,
                },
                success: function(data) {
                    if (data.status == 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 500
                            });

                        $($modal_cpkoFeedLog).modal('hide');
                        $($modal_feedLog).modal('hide');
                        initialize_page();

                    } else {
                        Swal.fire(data.message, "", "error");
                    }
                }
            });
        };
        function ajax_postCancelRundown($idTraceHead, $idBalanceHead, $traceNo){
            $.ajax({
                url: post_url,
                method: "POST",
                dataType: "JSON",
                data:{
                    flag: 'post_cancelRundown',
                    idTraceHead: $idTraceHead,
                    idBalanceHead: $idBalanceHead,
                    traceNo: $traceNo,
                },
                success: function(data) {
                    if (data.status == 1) {
                        Swal.fire({
                            position: 'top-end',
                            icon: 'success',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 500
                            });

                        $($modal_rundownLog).modal('hide');
                        initialize_page();

                    } else {
                        Swal.fire(data.message, "", "error");
                    }
                }
            });
        };
        function ajax_dtCpkoFeed($id, $feedId){
            return new Promise((resolve) => {
                $($id).DataTable().destroy();

                $($id).DataTable({
                    processing: true,
                    serverSide: true,
                    deferRender:true,
                    ajax: {
                        url: show_url,
                        data: {
                            flag: 'get_dtFeed',
                            mode: 'LATEST',
                            feedId: $feedId,
                            plant: "{{ $selectedPlant ?? '' }}"
                        },
                        complete: resolve
                    },
                    order: [[ 0, 'asc']],
                    responsive: true,
                    searching: false,
                    paging: false,
                    info: false,
                    columnDefs: [{
                        targets: [3,4], // index of 'balance_supplier' column
                        createdCell: function(td, cellData, rowData) {
                            if (rowData.out_qty === rowData.balance_supplier) {
                                $(td).css('color', 'green');
                            } else {
                                $(td).css('color', 'red');
                            }
                        }
                    }],
                    columns: [
                        { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center'},
                        { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                        { data: 'material_document', name: 'material_document', className: 'text-center'},
                        // { data: 'last_qtf', name: 'last_qtf', className: 'text-right'},
                        // { data: 'curr_qtf', name: 'curr_qtf', className: 'text-right'},
                        { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                        { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                        { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                    ]
                });
            })
        };
        function ajax_dtFeed($id, $feedId){
            return new Promise((resolve) => {
                $($id).DataTable().destroy();

                $($id).DataTable({
                    processing: true,
                    serverSide: true,
                    deferRender:true,
                    ajax: {
                        url: show_url,
                        data: {
                            flag: 'get_dtFeed',
                            mode: 'LATEST',
                            feedId: $feedId,
                            plant: "{{ $selectedPlant ?? '' }}"
                        },
                        complete: resolve
                    },
                    order: [[ 0, 'asc']],
                    responsive: true,
                    searching: false,
                    paging: false,
                    info: false,
                    columnDefs: [{
                        targets: [3,4], // index of 'balance_supplier' column
                        createdCell: function(td, cellData, rowData) {
                            if (rowData.out_qty === rowData.balance_supplier) {
                                $(td).css('color', 'green');
                            } else {
                                $(td).css('color', 'red');
                            }
                        }
                    }],
                    columns: [
                        { data: 'to_trace_no', name: 'to_trace_no', className: 'text-center'},
                        { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                        { data: 'material_document', name: 'material_document', className: 'text-center'},
                        // { data: 'last_qtf', name: 'last_qtf', className: 'text-right'},
                        // { data: 'curr_qtf', name: 'curr_qtf', className: 'text-right'},
                        { data: 'out_qty', name: 'out_qty', className: 'text-right'},
                        { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                        { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                    ]
                });
            })
        };
        function ajax_dtRundown($id, $rundownId){
            return new Promise((resolve) => {
                $($id).DataTable().destroy();

                $($id).DataTable({
                    processing: true,
                    serverSide: true,
                    deferRender:true,
                    ajax: {
                        url: show_url,
                        data: {
                            flag: 'get_dtRundown',
                            mode: 'LATEST',
                            rundownId: $rundownId,
                            plant: "{{ $selectedPlant ?? '' }}"
                        },
                        complete: resolve
                    },
                    order: [[ 0, 'asc']],
                    responsive: true,
                    searching: false,
                    paging: false,
                    info: false,
                    columnDefs: [{
                        targets: [3,4], // index of 'balance_supplier' column
                        createdCell: function(td, cellData, rowData) {
                            if (rowData.in_qty === rowData.balance_supplier) {
                                $(td).css('color', 'green');
                            } else {
                                $(td).css('color', 'red');
                            }
                        }
                    }],
                    columns: [
                        { data: 'rundown_trace_no', name: 'rundown_trace_no', className: 'text-center'},
                        { data: 'entry_date', name: 'entry_date', className: 'text-center'},
                        { data: 'material_document', name: 'material_document', className: 'text-center'},
                        // { data: 'last_qtf', name: 'last_qtf', className: 'text-right'},
                        // { data: 'curr_qtf', name: 'curr_qtf', className: 'text-right'},
                        { data: 'in_qty', name: 'in_qty', className: 'text-right'},
                        { data: 'balance_supplier', name: 'balance_supplier', className: 'text-right'},
                        { data: 'supplier', name: 'supplier', className: 'text-left', width:'35%'}
                    ]
                });
            })
        };


    /* FUNCTION DYNAMICS */
        function populate_hideAllSectionCard(){
            $($card_section101).hide();
            $($card_section103).hide();
            $($card_section110).hide();
            $($card_section111).hide();
            $($card_section104).hide();
            $($card_section302).hide();
            $($card_section105).hide();
            $($card_section106).hide();
            $($card_section112).hide();
        };
        function populate_showAllSectionCard(){
            $($card_section101).show();
            $($card_section103).show();
            $($card_section110).show();
            $($card_section111).show();
            $($card_section104).show();
            $($card_section302).show();
            $($card_section105).show();
            $($card_section106).show();
            $($card_section112).show();
        };
        function populate_routeToGlySectionCard(){
            $($card_section101).show();
            $($card_section103).show();
            $($card_section110).show();
            $($card_section111).show();
        };
        function populate_routeToWmeSectionCard(){
            $($card_section101).show();
            $($card_section103).show();
            $($card_section104).show();
            $($card_section302).show();
        }

    /* FUNCTION INITIALIZATION */
        async function initialize_page(){
            await ajax_dtCpkoFeed($dt_cpkoFeed, $feedId_cpko);
            await ajax_dtRundown($dt_daoilRundown, $rundownId_daoil);
            await ajax_dtRundown($dt_pkfadRundown, $rundownId_pkfad);
            await ajax_dtFeed($dt_daoilFeed, $feedId_daoil);
            await ajax_dtRundown($dt_crudemeRundown, $rundownId_crudeme);
            await ajax_dtRundown($dt_treatedglyRundown, $rundownId_treatedgly);
            // await ajax_dtRundown($dt_me60Rundown, $rundownId_me60);
            await ajax_dtFeed($dt_crudemeFeed, $feedId_crudeme);
            await ajax_dtRundown($dt_bdmeRundown, $rundownId_bdme);
            await ajax_dtRundown($dt_umeRundown, $rundownId_ume);
            await ajax_dtRundown($dt_me28Rundown, $rundownId_me28);
            await ajax_dtRundown($dt_econoate665_Rundown, $rundownId_econoate665);
            await ajax_dtRundown($dt_me80_104Rundown, $rundownId_104_me80);
            await ajax_dtRundown($dt_crudeglyRundown, $rundownId_crudegly);
            await ajax_dtRundown($dt_glycerineRundown, $rundownId_glycerine);
            await ajax_dtFeed($dt_umeFeed, $feedId_ume);
            await ajax_dtRundown($dt_wmeRundown, $rundownId_wme);
            await ajax_dtRundown($dt_me28_302Rundown, $rundownId_me28_302);
            await ajax_dtRundown($dt_cfa28Rundown, $rundownId_cfa28);
            await ajax_dtRundown($dt_ecorolwaxRundown, $rundownId_ecorolwax);
            await ajax_dtRundown($dt_lefaRundown, $rundownId_lefa);
            await ajax_dtRundown($dt_fa24Rundown, $rundownId_fa24);
            await ajax_dtRundown($dt_fa16Rundown, $rundownId_fa16);
            await ajax_dtRundown($dt_fa18lrrRundown, $rundownId_fa18lrr);

            await ajax_dtFeed($dt_treatedglyFeed, $feedId_treatedgly);
            await ajax_dtFeed($dt_crudeglyFeed, $feedId_crudegly);
            await ajax_dtFeed($dt_me28Feed, $feedId_me28);
            await ajax_dtFeed($dt_cfa28Feed, $feedId_cfa28);

            await ajax_dtFeed($dt_112fa24_Feed, $feedId_112_fa24);
            await ajax_dtRundown($dt_112cfa28_Rundown, $rundownId_112_cfa28);
            await ajax_dtRundown($dt_112fa1299_Rundown, $rundownId_112_fa12);
            await ajax_dtRundown($dt_112fa14lrr_Rundown, $rundownId_112_fa14lrr);

            await ajax_dtFeed($dt_112fa14lrr_m2_Feed, $feedId_112_fa14lrr);
            await ajax_dtRundown($dt_112cfa28_m2_Rundown, $rundownId_112_cfa28);
            await ajax_dtRundown($dt_112fa1499_m2_Rundown, $rundownId_112_fa14);

            await ajax_dtFeed($dt_112fa18lrr_m4_Feed, $feedId_112_fa18lrr);
            await ajax_dtRundown($dt_112cfa28_m4_Rundown, $rundownId_112_cfa28);
            await ajax_dtRundown($dt_112fa1899_m4_Rundown, $rundownId_112_fa18);
            await ajax_dtRundown($dt_112ecowax_m4_Rundown, $rundownId_112_ecowax);

            await ajax_dtFeed($dt_112ecowax_m3_Feed, $feedId_112_ecowax);
            await ajax_dtRundown($dt_112cfa28_m3_Rundown, $rundownId_112_cfa28);
            await ajax_dtRundown($dt_112fa18lrr_m3_Rundown, $rundownId_112_fa18lrr);
            await ajax_dtRundown($dt_112ecowax_m3_Rundown, $rundownId_112_ecowax);

            await ajax_dtRundown($dt_106_fa1299_m2_Rundown, $rundownId_106_fa1299);
            await ajax_dtRundown($dt_106_fa1499_m2_Rundown, $rundownId_106_fa1499);

            await ajax_dtFeed($dt_me80Feed, $feedId_105_me80);
            await ajax_dtRundown($dt_cfa80_105Rundown, $rundownId_105_cfa80);

            //populate_hideAllSectionCard();
            //$($card_section106).show();
        };

    /* FUNCTION AUTO-REFRESH */



</script>
@endpush

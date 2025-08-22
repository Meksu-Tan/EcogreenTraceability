@extends('layouts.app_user')
@section('title', 'Warehouse Stock')
@section('content')

<section class="section">
    <div class="section-body">
        <div class="row">
			<div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @include('user.trans_package.partials.__buttonHeader')
                    </div>
                </div>
				<div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div style="padding-left:25px; padding-bottom:10px">
                                <button type="button" id="supplier-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> Stock Card Summary </button>
                                <button type="button" id="supplier-add" class="btn btn-primary" style="color:white"><i class="fab fa-bity" aria-hidden="true"></i> Stock Card Detail </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table table-striped dataTable no-footer" id="table-supplier" width="100%" role="grid" aria-describedby="table-1_info">
                                    <thead>
                                        <tr>
                                            <th width="7%">No</th>
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
</section>

@endsection

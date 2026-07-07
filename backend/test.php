<?php

use Modules\TsWip\Http\Requests\UpdateWipProcessStepRequest;

$req = UpdateWipProcessStepRequest::create('/api/dummy', 'PUT', ['section_id' => 4, 'step_type' => 'feed', 'label' => 'test', 'feed_id' => 'test', 'sort_order' => 10, 'status' => 1]);
$val = validator($req->all(), (new UpdateWipProcessStepRequest)->rules());
echo json_encode($val->errors()->toArray());

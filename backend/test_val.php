<?php

use Illuminate\Contracts\Console\Kernel;
use Modules\TsWip\Http\Requests\UpdateWipProcessStepRequest;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$payload = [
    'section_id' => 4,
    'parent_step_id' => null,
    'step_type' => 'feed',
    'label' => 'ME28 FEEDS (105 FQ104)',
    'feed_id' => '006-01',
    'rundown_id' => null,
    'pipe_number' => null,
    'dcs_tag' => '105_FQ104',
    'mode_group' => 'MODE_105',
    'mode_value' => 'ME 28',
    'conditions' => null,
    'mode_options' => null,
    'sort_order' => 3,
    'status' => 1,
];
$val = validator($payload, (new UpdateWipProcessStepRequest)->rules());
echo json_encode($val->errors()->toArray());

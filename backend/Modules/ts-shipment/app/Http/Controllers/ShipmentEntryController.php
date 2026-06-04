<?php declare(strict_types=1);

namespace Modules\TsShipment\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ShipmentEntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Shipment Entry module ready (implementations pending)', 200);
    }

    public function store(Request $request): JsonResponse
    {
        return ApiResponse::success([], 'Shipment Entry stored successfully', 200);
    }

    public function destroy($id): JsonResponse
    {
        return ApiResponse::success(null, 'Shipment Entry deactivated', 200);
    }

    public function getShipmentBatchPackaging(Request $request): JsonResponse
    {
        try {
            $batchNo = $request->input('batchNo');
            if (!$batchNo) {
                return ApiResponse::error('Batch number is required', 422);
            }

            $db = DB::connection('eudr_ts')->select('SELECT a.entry_date, a.tf_number, a.batch_no, a.spec, a.production_order,
                                     a.lot_qty, a.qty, a.product, b.id_process, c.id_packing, d.id_pallet,
                                     CONCAT(b.id_process, " , ", b.code, " , ", b.description) AS process,
                                     CONCAT(c.code, " , ", c.description) AS packing,
                                     CONCAT(d.code, " , ", d.description) AS pallet,
                                     e.url_link AS label_link, f.url_link AS splabel_link,
                                     g.url_link AS csmark_link, a.id_special_label, a.id_customer_mark,
                                     CONCAT(a.id_tank, ",", a.tf_number) AS id_tank, a.csmark_isCheck, a.splabel_isCheck,
                                     CONCAT(a.id_product, ",", a.product) AS id_product, a.long_text,
                                     a.approved_by, a.approved_at,
                                     a.created_by, a.id_prdexecution, a.created_at,
                                     a.status, e.id_label, h.id_customer, CONCAT(h.code, " , ", h.description) AS customer,
                                     CONCAT(e.description) AS label, CONCAT(f.description) AS splabel,
                                     CONCAT(g.description) AS csmark, a.updated_by, UPPER(a.uom) AS uom,
                                     a.updated_at AS updated_at, a.finished_by, a.finished_at,
                                     a.p_label_link, a.p_splabel_link, a.p_csmark_link, a.tank_data, a.started_at, a.started_by
                                FROM oee_756.t_prd_execution a
                                LEFT JOIN oee_756.m_process b
                                  ON a.id_process = b.id_process
                                LEFT JOIN oee_756.m_packing c
                                  ON a.id_packing = c.id_packing
                                LEFT JOIN oee_756.m_pallet d
                                  ON a.id_pallet = d.id_pallet
                                LEFT JOIN oee_756.m_label e
                                  ON a.id_label = e.id_label
                                LEFT JOIN oee_756.m_special_label f
                                  ON a.id_special_label = f.id_label
                                LEFT JOIN oee_756.m_customer_mark g
                                  ON a.id_customer_mark = g.id_label
                                LEFT JOIN oee_756.m_customer h
                                  ON a.id_customer = h.id_customer
                               WHERE a.batch_no = ?
                               ORDER BY a.id_prdexecution DESC
                               LIMIT 1', [$batchNo]);

            return ApiResponse::success($db, 'Shipment batch packaging retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get shipment batch packaging: ' . $e->getMessage(), 500);
        }
    }

    public function getPreparationRecord(Request $request): JsonResponse
    {
        try {
            $batchNo = $request->input('batchNo');
            if (!$batchNo) {
                return ApiResponse::error('Batch number is required', 422);
            }

            $db = DB::connection('eudr_ts')->select('SELECT a.id_prepentry, a.id_prdexecution, a.batch_no, a.type,
                                     a.description, a.created_by, a.created_at, a.updated_at, a.status
                                FROM oee_756.t_prep_entry a
                               WHERE a.batch_no = ?
                               ORDER BY a.type ASC, a.created_at ASC', [$batchNo]);

            return ApiResponse::success($db, 'Preparation records retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get preparation records: ' . $e->getMessage(), 500);
        }
    }

    public function getLabel(Request $request): JsonResponse
    {
        try {
            $label = $request->input('label');
            if (!$label) {
                return ApiResponse::error('Label is required', 422);
            }

            $db = DB::connection('eudr_ts')->select('SELECT a.url_link
                                FROM oee_756.m_label a
                               WHERE a.status = "1"
                                 AND a.id_label = ?', [$label]);

            return ApiResponse::success($db, 'Label retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get label: ' . $e->getMessage(), 500);
        }
    }

    public function getSpecialLabel(Request $request): JsonResponse
    {
        try {
            $label = $request->input('label');
            if (!$label) {
                return ApiResponse::error('Label is required', 422);
            }

            $db = DB::connection('eudr_ts')->select('SELECT a.url_link
                                FROM oee_756.m_special_label a
                               WHERE a.status = "1"
                                 AND a.id_label = ?', [$label]);

            return ApiResponse::success($db, 'Special label retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get special label: ' . $e->getMessage(), 500);
        }
    }

    public function getCustomerMark(Request $request): JsonResponse
    {
        try {
            $label = $request->input('label');
            if (!$label) {
                return ApiResponse::error('Label is required', 422);
            }

            $db = DB::connection('eudr_ts')->select('SELECT a.url_link
                                FROM oee_756.m_customer_mark a
                               WHERE a.status = "1"
                                 AND a.id_label = ?', [$label]);

            return ApiResponse::success($db, 'Customer mark retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to get customer mark: ' . $e->getMessage(), 500);
        }
    }

    public function getDatShipment(Request $request): JsonResponse
    {
        try {
            $batchNo = $request->input('batchNo');
            $soNo = $request->input('soNo');
            $soItem = $request->input('soItem');

            $sapClient = 'Client=PRD-300';
            $sapReqUrl = 'http://eows.ecogreenoleo.co.id/general.php?';
            $sapFm     = '&FM=ZFM_EUDR_SHIPMENT';
            $input_1   = '&SO_NUM=' . $soNo;
            $input_2   = '&SO_ITEM=' . $soItem;
            $input_3   = '&BATCH=' . $batchNo;

            if ($batchNo == "FB" || $batchNo == "IS" || $batchNo == "VS") {
                $eobUrl = $sapReqUrl . $sapClient . $sapFm . $input_1 . $input_2;
            } else {
                $eobUrl = $sapReqUrl . $sapClient . $sapFm . $input_1 . $input_2 . $input_3;
            }

            $response = Http::timeout(30)->get($eobUrl);
            if ($response->failed()) {
                return ApiResponse::error('SAP request failed: ' . $response->body(), 500);
            }

            $data = $response->json();
            return ApiResponse::success($data, 'Shipment detail from SAP retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to query SAP: ' . $e->getMessage(), 500);
        }
    }

    public function getDatSoAllocation(Request $request): JsonResponse
    {
        try {
            $batchNo = $request->input('batchNo');
            if (!$batchNo) {
                return ApiResponse::error('Batch number is required', 422);
            }

            $sapClient = 'Client=PRD-300';
            $sapReqUrl = 'http://eows.ecogreenoleo.co.id/general.php?';
            $sapFm     = '&FM=ZFM_AD001';
            $input_1   = '&BATCH_NO=' . $batchNo;

            $eobUrl = $sapReqUrl . $sapClient . $sapFm . $input_1;

            $response = Http::timeout(30)->get($eobUrl);
            if ($response->failed()) {
                return ApiResponse::error('SAP request failed: ' . $response->body(), 500);
            }

            $data = $response->json();
            return ApiResponse::success($data, 'SO allocation from SAP retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to query SAP: ' . $e->getMessage(), 500);
        }
    }
}


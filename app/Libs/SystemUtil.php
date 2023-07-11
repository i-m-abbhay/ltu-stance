<?php

namespace App\Libs;

use App\Models\ApprovalDetail;
use App\Models\ApprovalHeader;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Department;
use App\Models\StaffDepartment;
use App\Models\Authority;
use App\Models\Notice;
use App\Models\Staff;
use App\Models\Matter;
use App\Models\Quote;
use App\Models\Order;
use App\Models\ProductMove;
use App\Models\ProductstockShelf;
use Illuminate\Support\Facades\Auth;
use App\Models\QuoteVersion;
use App\Models\QuoteDetail;
use Illuminate\Support\Collection;
use Mockery\Matcher\Not;
use DB;
use App\Libs\LogUtil;
use App\Models\Qr;
use App\Models\QrDetail;
use Storage;
use File;
use App\Libs\Common;
use App\Models\OrderDetail;
use App\Models\UpdateHistory;
use App\Models\Company;
use Exception;
use App\Exceptions\ValidationException;
use App\Models\Credited;
use App\Models\Loading;
use App\Models\LockManage;
use App\Models\Payment;
use App\Models\Requests;
use App\Libs\Session;
use App\Models\Reserve;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\Shipment;
use App\Models\ShipmentDetail;

/**
 * SystemUtil
 * このシステム固有の共通処理など
 */
class SystemUtil
{
    /**
     * 案件名作成
     * ※顧客名略称 または 顧客ID どちらかを渡す
     *
     * @param string $ownerName 施主名
     * @param string $customerShortName 顧客名略称
     * @param string $customerId 顧客ID
     * @param string $ym 年月
     * @return void
     */
    public function createMatterName($ownerName, $customerShortName = null, $customerId = null, $ym = null)
    {
        if (is_null($ym)) {
            // 年月取得
            $ym = Carbon::today()->format('Ym');
        }

        if (is_null($customerShortName)) {
            if (is_null($customerId)) {
                $customerShortName = '';
            } else {
                // 顧客名略称取得
                $Customer = new Customer();
                $cusotmerData = $Customer->find($customerId);
                $customerShortName = $cusotmerData['customer_short_name'];
            }
        }

        return $customerShortName . '_' . $ownerName . '_' . $ym;
    }

    /**
     * 見積申請
     *
     * @param [type] $model
     * @return void
     */
    public function applyForQuoteProcessing($model)
    {
        $result = false;

        $processKbn = $model->getTable();
        $processId = $model->id;

        $departmentId = null;

        $noticesParams = [];
        $noticeTemplate = [
            'notice_flg' => config('const.flg.off'),
            'staff_id' => null,
            'content' => config('message.notice.quote_approval_request_add'),
            'redirect_url' => url('quote-list/' . '?quote_no=' . $model->quote_no)
        ];

        // 案件の部門ID
        $Quote = new Quote();
        $Matter = new Matter();
        $Customer = new Customer();
        $Staff = new Staff();

        $quote = $Quote->getByQuoteNo($model->quote_no);
        $matter = $Matter->getByMatterNo($quote->matter_no);
        $customer = $Customer->getById($matter->customer_id);

        $departmentId = $matter->department_id;

        // 粗利率
        $applyVal = 0;
        if ($model->profit_total > 0 && $model->sales_total > 0) {
            $applyVal = Common::roundDecimalRate($model->profit_total / $model->sales_total * 100);
        }

        // 申請者(≠ログイン者)
        $applyStaffId = $model->staff_id;

        $approvalDetailParams = [];
        $finalApprovalOrder = 0;

        // 親部門の関連が無くなる or 既定値を超える迄
        $isAchieved = false;
        $isAutoApproved = false;
        $fullApprovalStaffIDs = $Staff->getStaffByAuthCode(config('const.auth.fullApproval'));
        $fullApprovalStaffIDs = $fullApprovalStaffIDs->map(function ($item, $key) {
            return $item->id;
        });
        for ($cnt = $approvalOrder = 0, $isLoop = true; $isLoop; $cnt++) {
            // 部門の関連数（自部門含む）が既定値を超えたら無限ループとみなす
            if ($cnt > config('const.judgeUpperLimit.parent_department')) {
                throw new \Exception(config('message.department.error.parent_circulate'));
            }

            $department = Department::where('id', '=', $departmentId)->first();
            $standardValue = $department->standard_gross_profit_rate;

            // 下記の場合は自動承認
            // 得意先ランクが設定済 && 申請値が得意先ランク（基準値）を満たしている
            if ($customer->customer_rank > 0 && $applyVal >= $customer->customer_rank) {
                $isAutoApproved = true;
            }

            if ($isAutoApproved) {
                // 得意先ランクが設定済 && 申請値が得意先ランク（基準値）を満たしている
                break;
            } else {
                if ($fullApprovalStaffIDs->search($department->chief_staff_id)) {
                    // 承認者が全承認権限を保持
                    if ($isAchieved) {
                        // nop
                    } else {
                        $finalApprovalOrder++;
                        if ($applyVal >= $standardValue) {
                            // 申請値が基準値以上に達した　※次の承認者から全承認権限保有
                            $isAchieved = true;
                        }
                    }
                } else {
                    // 承認者が全承認権限を未保持
                    if ($isAchieved) {
                        // 全承認権限保持者を追加する
                        $fullApprovalStaffIDs->push($department->chief_staff_id);
                    } else {
                        $approvalOrder++;
                        $finalApprovalOrder++;
                        $approvalDetailParams[] = [
                            'approval_staff_id' => $department->chief_staff_id,
                            'approval_order' => $finalApprovalOrder,
                        ];
                        $noticeTemplate['staff_id'] = $department->chief_staff_id;
                        $noticesParams[] = $noticeTemplate;
                        if ($applyVal >= $standardValue) {
                            // 申請値が基準値以上に達した　※次の承認者から全承認権限保有
                            $isAchieved = true;
                        }
                    }
                }
            }

            // 親部門の部門IDをセット
            $departmentId = $department->parent_id;

            // 親部門が存在しない場合はループから抜ける。
            if ($departmentId == 0 || $departmentId == null) {
                $isLoop = false;
            }
        }

        $ApprovalHeader = new ApprovalHeader();
        $ApprovalDetail = new ApprovalDetail();
        $Notice = new Notice();
        // 承認ヘッダ登録用パラメータ
        $approvalHeaderParams = [
            'process_kbn' => $processKbn, 'process_id' => $processId,
            'apply_staff_id' => $applyStaffId, 'final_approval_order' => $finalApprovalOrder,
        ];

        // 自動承認以外の場合は承認者が必ず存在するので、全承認権限者を追加する
        if (!$isAutoApproved) {
            foreach ($fullApprovalStaffIDs as  $value) {
                $approvalDetailParams[] = [
                    'approval_staff_id' => $value,
                    'approval_order' => config('const.fullApprovalOrder')
                ];
            }
        }

        // 申請データ登録
        $headerId = $ApprovalHeader->add($approvalHeaderParams);
        $ApprovalDetail->addList($approvalDetailParams, $headerId);
        $ApprovalHeader->updateStatusAutoJudgeById($headerId);

        if (count($noticesParams) > 0) {
            $Notice->addList($noticesParams);
        }
        $result = true;

        return $result;
    }

    /**
     * 承認処理（Rollbackは呼び出し側）
     *
     * @param [type] $processKbn
     * @param [type] $params['process_id'=>'処理ID', 'comment'=>'コメント', 'is_approval'=>'承認か？']
     * @return [int] 承認ヘッダのステータス
     */
    public function approvalForProcessing($processKbn, $params)
    {
        $result = false;

        $processId = $params['process_id'];
        $comment = $params['comment'];
        $detailStatus = ($params['is_approval']) ? config('const.approvalDetailStatus.val.approved') : config('const.approvalDetailStatus.val.sendback');

        $ApprovalHeader = new ApprovalHeader();
        $ApprovalDetail = new ApprovalDetail();

        $header = $ApprovalHeader->getByProcessId($processKbn, $processId, config('const.flg.off'), config('const.flg.on'))->first();
        if ($header->status == config('const.approvalHeaderStatus.val.approved') || $header->status == config('const.approvalHeaderStatus.val.sendback')) {
            throw new ValidationException(config('message.error.approval.status_changed'));
        }

        $details = $ApprovalDetail->getByHeaderId($header->id);
        $detailsUpdateKey = $details->search(function ($detail, $key) {
            return ($detail['approval_staff_id'] == Auth::id());
        });

        // 明細更新
        $ApprovalDetail->updateStatusById($details[$detailsUpdateKey]->id, $comment, $detailStatus);
        // 承認ヘッダのステータス更新
        $ApprovalHeader->updateStatusAutoJudgeById($header->id);
        $result = true;

        return $result;
    }

    /**
     * キャンセル処理（Rollbackは呼び出し側）
     *
     * @param int $processKbn 承認処理区分 （const.approvalProcessKbn）
     * @param int $processId 承認処理ID （見積版ID or 発注ID）
     * @return bool true:申請削除成功 false:申請削除失敗
     */
    public function cancelForProcessing($processKbn, $processId)
    {
        $result = false;

        $ApprovalHeader = new ApprovalHeader();
        $ApprovalDetail = new ApprovalDetail();

        $header = $ApprovalHeader->getByProcessId($processKbn, $processId, config('const.flg.off'), config('const.flg.on'))->first();
        
        if (!$header) {
            // 既にヘッダの過去フラグが立っていてデータが取得できないことがある ※quoteFallBelowGrossProfitRateProcessing
            $result = true;
        }else if($header->status == config('const.approvalHeaderStatus.val.not_approved')) {
            // 承認ヘッダ削除
            $ApprovalHeader->deleteById($header->id);
            // 承認明細削除
            $ApprovalDetail->deleteByHeaderId($header->id);

            $result = true;
        }

        return $result;
    }

    /**
     * 入出庫処理
     *
     * @param array $params
     * @return void
     */
    public function MoveInventory($params)
    {
        $result = false;
        $p = array();

        try {
            // パラメータ
            // product_id	
            // stock_flg			
            // product_code				
            // from_warehouse_id				
            // from_shelf_number_id				
            // to_warehouse_id				
            // to_shelf_number_id				
            // move_kind				
            // move_flg				
            // quantity				
            // order_id				
            // order_no				
            // order_detail_id				
            // reserve_id				
            // shipment_id				
            // shipment_detail_id				
            // arrival_id				
            // sales_id				
            // matter_id
            // customer_id				

            // 出庫元棚番在庫テーブル減算
            if ($params['from_warehouse_id'] != 0 && ($params['from_shelf_number_id'] != 0 || $params['stock_flg'] == 1)) {
                // 発注品の場合(案件ID、得意先IDがセットされている)
                if ($params['matter_id'] != 0 && $params['customer_id'] != 0) {
                    // 移動元倉庫ID、移動元棚ID、商品ID、案件ID、得意先IDで検索
                    $p['warehouse_id'] = $params['from_warehouse_id'];
                    $p['shelf_number_id'] = $params['from_shelf_number_id'];
                    $p['product_id'] = $params['product_id'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                }
                // 預かり品の場合(案件IDが「0」、得意先IDがセットされている)
                if ($params['matter_id'] == 0 && $params['customer_id'] != 0) {
                    // 移動元倉庫ID、商品ID、案件ID「0」、得意先IDで検索
                    $p['warehouse_id'] = $params['from_warehouse_id'];
                    $p['shelf_number_id'] = $params['from_shelf_number_id'];
                    $p['product_id'] = $params['product_id'];
                    $p['matter_id'] = 0;
                    $p['customer_id'] = $params['customer_id'];
                }
                // 在庫品の場合(案件IDが「0」、得意先IDが「0」)
                if ($params['matter_id'] == 0 && $params['customer_id'] == 0) {
                    // 移動元倉庫ID、商品ID、案件ID「0」、得意先ID「0」で検索
                    $p['warehouse_id'] = $params['from_warehouse_id'];

                    //移動種別が「倉庫移動」かつ棚番IDが渡された場合
                    if ($params['move_kind'] == 3 && array_key_exists('from_shelf_number_id', $params)) {
                        $p['shelf_number_id'] = $params['from_shelf_number_id'];
                    }
                    //返品棚から移動の場合
                    elseif (
                        ($params['move_kind'] == 4 || $params['move_kind'] == 7) && array_key_exists('is_return_shelf_from', $params)
                        && $params['is_return_shelf_from'] === true
                    ) {
                        $params['shelf_kind'] = 3;
                        //廃棄処理の場合
                    } elseif (
                        array_key_exists('is_discarded', $params)
                        && $params['is_discarded'] === true
                    ) {
                        $p['shelf_number_id'] = $params['from_shelf_number_id'];
                    } else {
                        $p['shelf_number_id'] = 0;
                        $params['shelf_kind'] = 0;
                    }
                    $p['product_id'] = $params['product_id'];
                    $p['matter_id'] = 0;
                    $p['customer_id'] = 0;
                }

                $p['shelf_kind'] = null;
                if (isset($params['shelf_kind'])) {
                    $p['shelf_kind']  = $params['shelf_kind'];
                }
                $ProductstockShelf = new ProductstockShelf();

                if ($p['shelf_kind'] !== null) {
                    $list = $ProductstockShelf->getListMoveInventory($p);
                } else {
                    $list = $ProductstockShelf->getList($p);
                }

                if (count($list) > 0) {
                    // 更新
                    $p['id'] = $list->pluck('id')[0];
                    $p['stock_flg'] = $params['stock_flg'];
                    $p['product_id'] = $params['product_id'];
                    $p['product_code'] = $params['product_code'];
                    $p['warehouse_id'] = $params['from_warehouse_id'];
                    $p['shelf_number_id'] = $list->pluck('shelf_number_id')[0];
                    $params['from_shelf_number_id'] = $list->pluck('shelf_number_id')[0];
                    $p['quantity'] = $list->pluck('quantity')[0] - $params['quantity'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                    $result = $ProductstockShelf->updateById($p);
                    // 0になったら削除
                    if ($p['quantity'] <= 0) {
                        $result = $ProductstockShelf->deleteById($p);
                    }
                }
            }

            // 出庫先棚番在庫テーブル加算
            if ($params['to_warehouse_id'] != 0 && ($params['to_shelf_number_id'] != 0 || $params['stock_flg'] == 1)) {
                // 発注品の場合(案件ID、得意先IDがセットされている)
                if ($params['matter_id'] != 0 && $params['customer_id'] != 0) {
                    // 移動先倉庫ID、移動先棚ID、商品ID、案件ID、得意先IDで検索
                    $p['warehouse_id'] = $params['to_warehouse_id'];
                    $p['shelf_number_id'] = $params['to_shelf_number_id'];
                    $p['product_id'] = $params['product_id'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                }
                // 預かり品の場合(案件IDが「0」、得意先IDがセットされている)
                if ($params['matter_id'] == 0 && $params['customer_id'] != 0) {
                    // 移動先倉庫ID、移動先棚ID、商品ID、案件IDが「0」、得意先IDで検索
                    $p['warehouse_id'] = $params['to_warehouse_id'];
                    $p['shelf_number_id'] = $params['to_shelf_number_id'];
                    $p['product_id'] = $params['product_id'];
                    $p['matter_id'] = 0;
                    $p['customer_id'] = $params['customer_id'];
                }
                // 在庫品の場合(案件IDが「0」、得意先IDが「0」)
                if ($params['matter_id'] == 0 && $params['customer_id'] == 0) {
                    // 移動先倉庫ID、商品ID、案件ID「0」、得意先ID「0」で検索
                    $p['warehouse_id'] = $params['to_warehouse_id'];
                    $p['shelf_number_id'] = 0;
                    $p['product_id'] = $params['product_id'];
                    $p['matter_id'] = 0;
                    $p['customer_id'] = 0;

                    //返品の場合は返品棚に加算
                    if ($params['move_kind'] == 4 && array_key_exists('is_return_shelf', $params) && $params['is_return_shelf'] === true) {
                        $params['shelf_kind'] = 3;
                    }
                }
                $p['id'] = null;
                $p['shelf_kind'] = null;

                if (isset($params['shelf_kind'])) {
                    $p['shelf_kind']  = $params['shelf_kind'];
                }

                $ProductstockShelf = new ProductstockShelf();

                if ($p['shelf_kind'] !== null) {
                    $list = $ProductstockShelf->getListMoveInventory($p);
                } else {
                    $list = $ProductstockShelf->getList($p);
                }

                if (count($list) > 0) {
                    // 更新
                    $p['id'] = $list->pluck('id')[0];
                    $p['stock_flg'] = $params['stock_flg'];
                    $p['product_id'] = $params['product_id'];
                    $p['product_code'] = $params['product_code'];
                    $p['warehouse_id'] = $params['to_warehouse_id'];
                    $p['shelf_number_id'] = $list->pluck('shelf_number_id')[0];
                    $params['to_shelf_number_id'] = $list->pluck('shelf_number_id')[0];
                    $p['quantity'] = $list->pluck('quantity')[0] + $params['quantity'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                    $result = $ProductstockShelf->updateById($p);
                } else {
                    // 登録
                    $p['stock_flg'] = $params['stock_flg'];
                    $p['product_id'] = $params['product_id'];
                    $p['product_code'] = $params['product_code'];
                    $p['warehouse_id'] = $params['to_warehouse_id'];
                    $p['shelf_number_id'] = $params['to_shelf_number_id'];
                    $p['quantity'] = $params['quantity'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                    $result = $ProductstockShelf->add($p);
                }
            }

            // 入出庫テーブル登録
            $ProductMove = new ProductMove();
            $id = $ProductMove->getId($params);
            if ($id == null) {
                $result = $ProductMove->add($params);
                $id = $result;
                if (!$result) {
                    throw new \Exception(config('message.error.save'));
                }
            }
        } catch (\Exception $e) {
            // Log::error($e);
            throw $e;
        }
        return $id;
    }


    /**
     * QR発行 (QRIDまたはQRコード、印刷枚数を渡す)
     *
     * @param $params qr_id or qr_code 
     * @param $printNum 印刷枚数
     * @return True:成功  False:失敗
     */
    public static function qrPrintManager($qrParams, $printNum)
    {
        $Qr = new Qr();
        if (!empty($qrParams['qr_id']) && !empty($printNum)) {
            // QRIDが渡された場合
            $qrData = $Qr->getById($qrParams['qr_id']);
            $countData = $Qr->getQrDetailCounts($qrParams);
        } else if (!empty($qrParams['qr_code']) && !empty($printNum)) {
            // QRコードが渡された場合
            $qrData = $Qr->getByQrCode($qrParams['qr_code']);
            $countData = $Qr->getQrDetailCounts($qrParams);
        } else {
            return false;
        }

        $qrData['qr_id'] = null;
        // 発行枚数
        $qrData['number_total'] = str_pad($printNum, 2, 0, STR_PAD_LEFT);
        $qrData['number_totals'] = str_pad($printNum, 2, 0, STR_PAD_LEFT);

        $qrData['qr'] = $qrData['qr_code'];
        $countTxt = '';
        if ($countData['count'] > 1) {
            $countTxt = '他' . ((int)$countData['count'] - 1) . '件';
        }
        $qrData['counts'] = $countTxt;

        $qrData['quantity'] = $qrData['quantity']. '個';

        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            // HTTPS通信の場合の処理
            $isSecure = true;
        }

        // 印刷用URL生成
        $query = '?__success_redirect_url=googlechrome://&__failure_redirect_url=' . url('/smapri-failed', null, $isSecure) . '&__format_archive_url=' . url(config('const.qrLabelPath') . config('const.printer.printTemplateName'), null, $isSecure) . '&__format_id_number=1&start_number=01';
        // $query .=  '&' . http_build_query($qrData->toArray());

        $query .= '&qr_code='. rawurlencode(SystemUtil::truncate($qrData['qr_code'], 14));
        $query .= '&matter_name='. rawurlencode(SystemUtil::truncate($qrData['matter_name'], 50));
        $query .= '&arrival_date='. rawurlencode(SystemUtil::truncate($qrData['arrival_date'], 40));
        $query .= '&customer_name='. rawurlencode(SystemUtil::truncate($qrData['customer_name'], 50));
        $query .= '&product_code='. rawurlencode(SystemUtil::truncate($qrData['product_code'], 40));
        $query .= '&product_name='. rawurlencode(SystemUtil::truncate($qrData['product_name'], 40));
        $query .= '&qr='. rawurlencode(SystemUtil::truncate($qrData['qr'], 10));
        $query .= '&counts='. rawurlencode($qrData['counts']);
        $query .= '&number_total='. rawurlencode($qrData['number_total']);
        $query .= '&number_totals='. rawurlencode($qrData['number_totals']);
        $query .= '&quantity='. rawurlencode(SystemUtil::truncate($qrData['quantity'], 10));

        // 使用端末がiosか判定
        $isIos = self::judgeDevice();

        // ドメイン取得
        // $domain = url('/', null, $isSecure);
        $domain = (empty($_SERVER["HTTPS"]) ? "http://" : "http://"). 'localhost';
        $host = parse_url($domain)['host'];
        $port = '';
        if ($isIos) {
            // ios端末
            $domain = 'smapri:';
            $url = $domain . '/Format/Print' . $query;
            return $url;
        } else {
            $port = ':' . config('const.printer.port');
            $url = $domain . $port . '/Format/Print' . $query;
            header('Location: '. $url);
            exit();
        }

        // $headers = [
        //     'Content-Type: text/html; charset=UTF-8',
        //     'Host: ' . $host . ':9090'
        // ];

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($qrData));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_URL, $domain . $port . '/Format/Print' . $query);

        // // 戻り値 
        // $result = curl_exec($ch);

        // // 結果
        // $curl_info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);

        // // HTTP_STATUS判定
        // switch ($curl_info) {
        //     case 200:
        //     case 302:
        $result = true;
        //         break;
        //     default:
        //         $result = false;
        //         break;
        // }

        return $result;
    }

    /**
     * 文字を丸める
     *
     * @param string $input         文字列
     * @param integer $capacity     バイト数
     * @param string $trimMarker　  末尾へ付け加える文字
     * @return string
     */
    public static function truncate($input, int $capacity = -1, $trimMarker = '')
    {
        // 無制限またはサイズ内
        if ($capacity < 0 || strlen($input . $trimMarker) <= $capacity) {
            return $input . $trimMarker;
        }

        // $trimMarker だけで $capacity を超えている
        if (strlen($trimMarker) > $capacity) {
            return '';
        }

        $input = SystemUtil::strReplaceManager($input);

        return mb_convert_encoding(mb_strcut(mb_convert_encoding($input, 'SJIS-win', 'UTF-8'), 0, $capacity, 'SJIS-win'), 'UTF-8', 'SJIS-win') . $trimMarker;
    }


    /**
     * 未対応文字の置換
     *
     * @param [type] $str
     * @return void
     */
    public static function strReplaceManager($str) 
    {
        // 波ダッシュ
        $str = str_replace(hex2bin("EFBD9E"), hex2bin("E3809C"), $str);
        // 全角マイナス
        $str = str_replace(hex2bin("E28892"), hex2bin("EFBC8D"), $str);

        return $str;
    }

    /**
     * デバイス判定
     * @param  string $ua ユーザエージェント
     * @return True: ios
     */
    public static function judgeDevice($ua = null)
    {
        if (is_null($ua)) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
        }

        if (preg_match('/iPhone|iPod|Macintosh|iPad/ui', $ua)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * グリッドに表示するデータに階層フィルター用のパスを付与する
     * 
     * @param Collection $gridData
     * @return Collection $gridData
     */
    public function addFilterTreePathInfo(Collection $gridData)
    {
        $treeData = [];
        foreach ($gridData as $cnt => $record) {
            if ($record->depth === config('const.quoteConstructionInfo.depth')) {
                // 工事区分
                $gridData[$cnt]->filter_tree_path = strval($cnt);
                $treeData[$record->quote_detail_id]['filter_tree_path'] = $record->filter_tree_path;
                $treeData[$record->quote_detail_id]['count'] = '0';
            } else {
                // 工事区分以外
                $depth = explode(config('const.treePathSeparator'), $record->tree_path);
                $current = null;
                foreach ($depth as $key => $treeId) {
                    if ($key === 0) {
                        $current = &$treeData[$treeId];
                    } else {
                        $current = &$current[$treeId];
                    }
                }
                $gridData[$cnt]->filter_tree_path = $current['filter_tree_path'] . config('const.treePathSeparator') . $current['count'];
                $current['count'] = strval(intval($current['count']) + 1);
                $current[$record->quote_detail_id]['filter_tree_path'] = $record->filter_tree_path;
                $current[$record->quote_detail_id]['count'] = '0';
                unset($current);
            }
        }
        return $gridData;
    }

    /**
     * 階層データの明細行を削除する
     * 
     * @param $quoteLayerList
     * @return void
     */
    public function deleteNotLayerRecord(&$quoteLayerList)
    {
        foreach ($quoteLayerList['items'] as $key => &$record) {
            if ($record['layer_flg'] === config('const.flg.off')) {
                unset($quoteLayerList['items'][$key]);
                $quoteLayerList['items'] = array_values($quoteLayerList['items']);
            }
            $this->deleteNotLayerRecord($record);
        }
    }

    /**
     * 見積階層合計計算
     *
     * @param [type] $quoteNo
     * @param [type] $quoteVersion
     * @return array 例：['見積明細ID' => ['cost_total'=>0, 'sales_total'=>0, 'profit_total'=>0, 'gross_profit_rate'=>0], '見積明細ID'...以下略]
     */
    public function calcQuoteLayersTotal($quoteNo, $quoteVersion)
    {
        $QuoteDetail = new QuoteDetail();
        $constructionParentId = config('const.quoteConstructionInfo.parent_quote_detail_id');

        $quoteDetails = $QuoteDetail->getByVer($quoteNo, $quoteVersion);
        $layers = $quoteDetails->where('layer_flg', config('const.flg.on'))->mapWithKeys(function ($item) {
            return [
                $item['id'] => [
                    'cost_unit_price' => 0, 'sales_unit_price' => 0,
                    'cost_makeup_rate' => 0, 'sales_makeup_rate' => 0,
                    'cost_total' => 0, 'sales_total' => 0,
                    'profit_total' => 0, 'gross_profit_rate' => 0,
                ]
            ];
        })->toArray();
        $layers[$constructionParentId] = [
            'cost_unit_price' => 0, 'sales_unit_price' => 0,
            'cost_makeup_rate' => 0, 'sales_makeup_rate' => 0,
            'cost_total' => 0, 'sales_total' => 0,
            'profit_total' => 0, 'gross_profit_rate' => 0,
        ];

        $maxDepth = $quoteDetails->max('depth');
        for ($i = $maxDepth; $i > -1; $i--) {
            // 見積明細取得（深さ別）
            $detailsByDepth = $quoteDetails->where('depth', $i);
            foreach ($detailsByDepth as $detail) {
                $detail->cost_total = (int) $detail->cost_total;
                $detail->sales_total = (int) $detail->sales_total;
                if ($detail->layer_flg) {
                    // 階層　※階層の仕入・販売単価（販売額不使用の場合）は明細の合計値
                    // 販売総計
                    if ($detail->sales_use_flg) {
                        // 販売額使用
                        $layers[$detail->id]['sales_unit_price'] = $detail->sales_unit_price;   // 販売単価をそのまま使用
                        $layers[$detail->id]['sales_total'] = $layers[$detail->id]['sales_unit_price'] * $detail->quote_quantity; // 自階層合計書き換え
                    } else {
                        // 販売額不使用(0除算不可)
                        if ($detail->quote_quantity != 0) {
                            $layers[$detail->id]['sales_unit_price'] = $layers[$detail->id]['sales_total'] / $detail->quote_quantity; // 販売単価は自階層合計÷数量
                        }
                    }
                    $layers[$detail->parent_quote_detail_id]['sales_total'] += $layers[$detail->id]['sales_total']; // 自階層合計を親階層合計に加算

                    // 仕入計算(0除算不可)
                    if ($detail->quote_quantity != 0) {
                        $layers[$detail->id]['cost_unit_price'] = $layers[$detail->id]['cost_total'] / $detail->quote_quantity; // 仕入単価は自階層合計÷数量
                    }
                    $layers[$detail->parent_quote_detail_id]['cost_total'] += $layers[$detail->id]['cost_total'];   // 自階層合計を親階層合計に加算

                    // 掛率(0除算不可)
                    if ($detail->regular_price != 0) {
                        $layers[$detail->id]['sales_makeup_rate'] = Common::roundDecimalRate($layers[$detail->id]['sales_unit_price'] / $detail->regular_price * 100);
                        $layers[$detail->id]['cost_makeup_rate'] = Common::roundDecimalRate($layers[$detail->id]['cost_unit_price'] / $detail->regular_price * 100);
                    }

                    // 粗利総額、粗利率計算
                    $layers[$detail->id]['profit_total'] = $layers[$detail->id]['sales_total'] - $layers[$detail->id]['cost_total'];
                    // (0除算不可)
                    if ($layers[$detail->id]['sales_total'] != 0) {
                        $layers[$detail->id]['gross_profit_rate'] = Common::roundDecimalRate($layers[$detail->id]['profit_total'] / $layers[$detail->id]['sales_total'] * 100);
                    }
                } else {
                    // 明細(親階層の合計に加算:仕入,売上)
                    $layers[$detail->parent_quote_detail_id]['cost_total'] += $detail->cost_total;
                    $layers[$detail->parent_quote_detail_id]['sales_total'] += $detail->sales_total;
                }
            }
        }
        $layers[$constructionParentId]['profit_total'] = $layers[$constructionParentId]['sales_total'] - $layers[$constructionParentId]['cost_total'];
        if ($layers[$constructionParentId]['sales_total'] != 0) {
            $layers[$constructionParentId]['gross_profit_rate'] = Common::roundDecimalRate($layers[$constructionParentId]['profit_total'] / $layers[$constructionParentId]['sales_total'] * 100);
        }
        return $layers;
    }

    /**
     * 変更履歴に登録（Rollbackは呼び出し側）
     *
     * @param [type] $kbn 区分
     * @param [type] $oldData[i][key] ヘッダー主キー
     * @param [type] $oldData[i][key] 変更前データ
     * @param [type] $newData[i][key] 変更後データ
     * @return void
     */
    public function addUpdateHistory($kbn, $headerKey, $beforeData, $afterData)
    {
        $result = false;

        $data = [];
        $columnComment = null;
        switch ($kbn) {
            case config('const.updateHistoryKbn.val.quote_detail'):
                $columnComment = (new QuoteDetail())->getColumnComment();
                break;
            case config('const.updateHistoryKbn.val.order_detail'):
                $columnComment = (new OrderDetail())->getColumnComment();
                break;
        }

        foreach ($beforeData as $key => $value) {
            // 更新後データにキーが存在しない場合はスキップ 
            if (!isset($afterData[$key])) {
                continue;
            }

            // 更新者と更新日時は除外
            if ($key === 'update_at' || $key === 'update_user') {
                continue;
            }

            // keyの末尾4文字がdateの場合はハイフンをスラッシュに変換
            if (mb_substr($key, -4) == 'date') {
                $beforeData[$key] = str_replace('-', '/', $beforeData[$key]);
                $afterData[$key] = str_replace('-', '/', $afterData[$key]);
            }

            // 更新前の値 != 更新後の値
            if ($beforeData[$key] != $afterData[$key]) {
                $data[] = array(
                    'kbn' => $kbn,
                    'header_key' => $headerKey,
                    'detail_key' => $beforeData['id'],
                    'p_col_name' => $key,
                    'l_col_name' => $columnComment[$key],
                    'from_val' => $beforeData[$key],
                    'to_val' => $afterData[$key],
                );
            }
        }

        // 更新対象が無いならTRUE
        if (count($data) > 0) {
            $result = (new UpdateHistory())->addList($data);
        } else {
            $result = true;
        }

        return $result;
    }

    /**
     * Qrのヘッダと明細を削除する
     * 
     * @param $qrId
     * @return void
     */
    public function deleteQr($qrId)
    {
        $result = false;

        try {

            $Qr = new Qr();
            $QrDetail = new QrDetail();
            $qrResult = $Qr->deleteByQrId($qrId);
            $qrDetailResult = $QrDetail->deleteByDetailQrId($qrId);


            if ($qrResult >= 0 && $qrDetailResult >= 0) {
                $result = true;
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
        return $result;
    }

    /**
     * 発注書データ取得
     *
     * @param [type] $orderId 発注ID
     * @return void
     */
    public function getOrderReportData($orderId)
    {
        $Company = new Company();
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $QuoteDetail = new QuoteDetail();

        $company = $Company::all()->first();
        $order = $Order->getOrderReportDataById($orderId);
        $orderDetail = $OrderDetail->getOrderReportDataByOrderId($orderId);

        // 基本情報
        $data[config('const.orderReport.jpn_key.basic')['parentkey']] = [
            config('const.orderReport.jpn_key.basic')['order_no'] => $order->order_no,               // 注文No
            config('const.orderReport.jpn_key.basic')['company'] => $company->company_name,          // 会社名
            config('const.orderReport.jpn_key.basic')['department'] => $order->matter_department,    // 担当部門
            config('const.orderReport.jpn_key.basic')['supplier'] => $order->supplier_name,          // 仕入先名
            config('const.orderReport.jpn_key.basic')['maker'] => $order->maker_name,                // メーカー名
            config('const.orderReport.jpn_key.basic')['order_date'] => $order->order_datetime,       // 発行年月日
            config('const.orderReport.jpn_key.basic')['chief_staff'] => $order->chief_staff,         // 責任者
            config('const.orderReport.jpn_key.basic')['sales_staff'] => $order->matter_staff,        // 営業担当者
            config('const.orderReport.jpn_key.basic')['order_staff'] => $order->order_staff,         // 発注担当者
        ];
        // 配送先情報
        $data[config('const.orderReport.jpn_key.shipping')['parentkey']] = [
            config('const.orderReport.jpn_key.shipping')['delivery_name'] => $order->delivery_name,
            config('const.orderReport.jpn_key.shipping')['tel'] => $order->department_tel,
            config('const.orderReport.jpn_key.shipping')['delivery_address'] => $order->delivery_address,
            config('const.orderReport.jpn_key.shipping')['customer'] => ($order->account_customer_name) ? $order->account_customer_name : $order->customer_name,
            config('const.orderReport.jpn_key.shipping')['site'] => ($order->account_owner_name) ? $order->account_owner_name : $order->owner_name,
            config('const.orderReport.jpn_key.shipping')['memo'] => $order->supplier_comment,
        ];

        // 見積明細の親明細取得（親1階層分で十分）
        $parentQuoteDetail = $QuoteDetail->getByIds($orderDetail->pluck('parent_quote_detail_id')->unique()->toArray());

        // 内容（明細部分）
        $data[config('const.orderReport.jpn_key.detail')['parentkey']] = [];
        if ($orderDetail->count() > 0) {
            $keepConstruction = $orderDetail->first()->construction_id;
            $exclusionQDetailIDs = [];
            for ($i = 0, $lineCnt = 0, $seqNo = 1; $i < count($orderDetail); $i++) {
                if (config('const.orderReport.number_of_lines') == $lineCnt) {
                    $lineCnt = 0;
                }
                $nowConstruction = $orderDetail[$i]->construction_id;
                // 工事区分が変わったら改ページ
                if ($keepConstruction != $nowConstruction) {
                    $keepConstruction = $nowConstruction;
                    for ($j = $lineCnt; $j < config('const.orderReport.number_of_lines'); $j++) {
                        $data[config('const.orderReport.jpn_key.detail')['parentkey']][] = [
                            config('const.orderReport.jpn_key.detail')['no'] => null,
                            config('const.orderReport.jpn_key.detail')['product_code'] => null,
                            config('const.orderReport.jpn_key.detail')['product_name'] => null,
                            config('const.orderReport.jpn_key.detail')['model'] => null,
                            config('const.orderReport.jpn_key.detail')['order_quantity'] => null,
                            config('const.orderReport.jpn_key.detail')['unit'] => null,
                            config('const.orderReport.jpn_key.detail')['cost_unit_price'] => null,
                            config('const.orderReport.jpn_key.detail')['desired_delivery_date'] => null,
                            config('const.orderReport.jpn_key.detail')['delivery_date'] => null,
                            config('const.orderReport.jpn_key.detail')['memo'] => null,
                        ];
                    }
                    $lineCnt = 0;
                }

                // 発注明細とリンクしている見積明細が一式階層の子明細である場合に一式行を出力
                $linkParentQuoteDetail = $parentQuoteDetail->firstWhere('id', $orderDetail[$i]->parent_quote_detail_id);
                if ($orderDetail[$i]->parent_quote_detail_id != 0 && $linkParentQuoteDetail->set_flg == config('const.flg.on')) {
                    // 見積明細IDが除外対象になっていない場合はヘッダ行出力
                    if (!in_array($orderDetail[$i]->quote_detail_id, $exclusionQDetailIDs)) {
                        $data[config('const.orderReport.jpn_key.detail')['parentkey']][] = [
                            config('const.orderReport.jpn_key.detail')['no'] => $seqNo++,                                          // No.
                            config('const.orderReport.jpn_key.detail')['product_code'] => $linkParentQuoteDetail->product_code,    // 商品コード
                            config('const.orderReport.jpn_key.detail')['product_name'] => $linkParentQuoteDetail->product_name,    // 商品名
                            config('const.orderReport.jpn_key.detail')['model'] => $linkParentQuoteDetail->model,                  // 規格
                            config('const.orderReport.jpn_key.detail')['order_quantity'] => 1,                                     // 数量
                            config('const.orderReport.jpn_key.detail')['unit'] => $linkParentQuoteDetail->unit,                    // 単位
                            config('const.orderReport.jpn_key.detail')['cost_unit_price'] => (int) $linkParentQuoteDetail->cost_unit_price,   // 仕入単価
                            config('const.orderReport.jpn_key.detail')['desired_delivery_date'] => $order->desired_delivery_date,             // 納期依頼
                            config('const.orderReport.jpn_key.detail')['delivery_date'] => null,                           // 納期回答（null固定）
                            config('const.orderReport.jpn_key.detail')['memo'] => null,                                    // 備考
                        ];
                        // 一度計算に使用した一式階層配下の見積明細IDは一式行出力の除外対象とする
                        $tmp = $orderDetail->where('parent_quote_detail_id', $orderDetail[$i]->parent_quote_detail_id)->pluck('quote_detail_id')->toArray();
                        $exclusionQDetailIDs = array_merge($exclusionQDetailIDs, $tmp);
                        $lineCnt++;
                    }
                }

                $data[config('const.orderReport.jpn_key.detail')['parentkey']][] = [
                    config('const.orderReport.jpn_key.detail')['no'] => $seqNo++,                                                      // No.
                    config('const.orderReport.jpn_key.detail')['product_code'] => $orderDetail[$i]->product_code,                    // 商品コード
                    config('const.orderReport.jpn_key.detail')['product_name'] => $orderDetail[$i]->product_name,                    // 商品名
                    config('const.orderReport.jpn_key.detail')['model'] => $orderDetail[$i]->model,                                  // 規格
                    config('const.orderReport.jpn_key.detail')['order_quantity'] => (float) $orderDetail[$i]->order_quantity,         // 数量
                    config('const.orderReport.jpn_key.detail')['unit'] => $orderDetail[$i]->unit,                                    // 単位
                    config('const.orderReport.jpn_key.detail')['cost_unit_price'] => (int) $orderDetail[$i]->cost_unit_price,       // 仕入単価
                    config('const.orderReport.jpn_key.detail')['desired_delivery_date'] => $order->desired_delivery_date,            // 納期依頼
                    config('const.orderReport.jpn_key.detail')['delivery_date'] => null,                                             // 納期回答（null固定）
                    config('const.orderReport.jpn_key.detail')['memo'] => $orderDetail[$i]->memo,                                    // 備考
                ];
                $lineCnt++;
            }
        } else {
            for ($i = 0; $i < config('const.orderReport.number_of_lines'); $i++) {
                $data[config('const.orderReport.jpn_key.detail')['parentkey']][] = [
                    config('const.orderReport.jpn_key.detail')['no'] => null,
                    config('const.orderReport.jpn_key.detail')['product_code'] => null,
                    config('const.orderReport.jpn_key.detail')['product_name'] => null,
                    config('const.orderReport.jpn_key.detail')['model'] => null,
                    config('const.orderReport.jpn_key.detail')['order_quantity'] => null,
                    config('const.orderReport.jpn_key.detail')['unit'] => null,
                    config('const.orderReport.jpn_key.detail')['cost_unit_price'] => null,
                    config('const.orderReport.jpn_key.detail')['desired_delivery_date'] => null,
                    config('const.orderReport.jpn_key.detail')['delivery_date'] => null,
                    config('const.orderReport.jpn_key.detail')['memo'] => null,
                ];
            }
        }

        return $data;
    }

    /**
     * 入力数の計算
     * @param $stockQuantity 管理数量
     * @param $minQuantity 最小単位数
     * @return 入力数
     */
    public function calcQuantity($stockQuantity, $minQuantity)
    {
        // 入力数
        return $stockQuantity * (float)$minQuantity;
    }

    /**
     * 管理数の計算
     * @param $quantity 数量
     * @param $minQuantity 最小単位数
     * @return 管理数
     */
    public function calcStock($quantity, $minQuantity)
    {
        // 管理数
        return $quantity / $minQuantity;
    }
    /**
     * 総額の計算
     * @param $quantity 数量
     * @param $unitPrice 単価
     * @param $isCost 仕入
     * @return 総額
     */
    public function calcTotal($quantity, $unitPrice, $isCost = false)
    {
        // 仕入総額 = 数量 * 仕入単価
        // 販売総額 = 数量 * 販売単価
        $result = 0;
        if ($isCost) {
            $result = Common::roundDecimalStandardPrice($quantity * $unitPrice);
        } else {
            $result = Common::roundDecimalSalesPrice($quantity * $unitPrice);
        }
        return $result;
    }
    /**
     * 粗利総額の計算
     * @param $salesTotal 販売総額
     * @param $costTotal 仕入れ総額
     * @return 粗利総額
     */
    public function calcProfitTotal($salesTotal, $costTotal)
    {
        // 粗利総額 = 販売総額 - 仕入総額
        return $salesTotal - $costTotal;
    }
    /**
     * 利率の計算
     * @param $dividend 
     * @param $divisor 除数
     * @return 利率
     */
    public function calcRate($dividend, $divisor)
    {
        $result = 0;
        // 粗利率 = 粗利総額 / 販売総額 * 100
        // 販売掛率 = 販売単価 / 定価 * 100
        if ($divisor != 0) {
            $result = Common::roundDecimalRate($dividend / $divisor * 100);
        }
        return $result;
    }

    /**
     * 見積0版の粗利率が規定値を下回っている場合に見積版の見積状況を「作成中」に戻す
     * 承認データは過去フラグを立てる
     * 
     * @param [type] $quoteNo
     * @param [type] $beforeQuoteVersion
     * @throws Exception
     * @return void
     */
    public function quoteFallBelowGrossProfitRateProcessing($quoteNo, $beforeQuoteVersion = null)
    {
        // 案件の部門ID
        $departmentId   = null;
        // 基準を下回った件数
        $fallBelowCnt   = 0;
        // 粗利率
        $beforeApplyVal = 0;
        $applyVal       = 0;

        $Quote          = new Quote();
        $QuoteVersion   = new QuoteVersion();
        $Matter         = new Matter();
        $Customer       = new Customer();
        $ApprovalHeader = new ApprovalHeader();

        $quote          = $Quote->getByQuoteNo($quoteNo);
        $quoteVersion   = $QuoteVersion->getByVer($quoteNo, config('const.quoteCompVersion.number'));
        $matter         = $Matter->getByMatterNo($quote->matter_no);
        $customer       = $Customer->getById($matter->customer_id);

        $processKbn     = config('const.approvalProcessKbn.quote');
        $processId      = $quoteVersion->id;

        $departmentId   = $matter->department_id;

        // 粗利率
        if($beforeQuoteVersion !== null){
            if (Common::nullorBlankToZero($beforeQuoteVersion->sales_total) > 0) {
                $beforeApplyVal = $this->calcRate(Common::nullorBlankToZero($beforeQuoteVersion->profit_total), Common::nullorBlankToZero($beforeQuoteVersion->sales_total));
            }
        }else{
            // 見積画面対応までの暫定対応
            $beforeApplyVal = null;
        }

        // 粗利率
        if (Common::nullorBlankToZero($quoteVersion->sales_total) > 0) {
            $applyVal = $this->calcRate(Common::nullorBlankToZero($quoteVersion->profit_total), Common::nullorBlankToZero($quoteVersion->sales_total));
        }

        // 粗利率に変動があるか
        if($beforeApplyVal === null || $beforeApplyVal != $applyVal){
            // 親部門の関連が無くなる or 既定値を超える迄
            for ($cnt = 0, $isLoop = true; $isLoop; $cnt++) {
                // 部門の関連数（自部門含む）が既定値を超えたら無限ループとみなす
                if ($cnt > config('const.judgeUpperLimit.parent_department')) {
                    throw new \Exception(config('message.department.error.parent_circulate'));
                }

                $department = Department::where('id', '=', $departmentId)->first();

                // 得意先ランク未設定 Or (得意先ランクが設定済み && 得意先ランク>申請値)
                //   ※得意先ランクが未設定 又は 申請値が得意先ランク未達の時は必ず承認行為が発生する
                if ($customer->customer_rank == 0 || ($customer->customer_rank > 0 && $customer->customer_rank > $applyVal)) {
                    // 1件でもあれば作成中に戻すので抜ける
                    $fallBelowCnt++;
                    break;
                }

                // 親部門の部門IDをセット
                $departmentId = $department->parent_id;

                // 親部門が存在しない場合はループから抜ける。
                if ($departmentId == 0 || $departmentId == null) {
                    $isLoop = false;
                }
            }

            if ($fallBelowCnt > 0) {
                // 基準値を下回った
                // 見積版の状況を作成中にする
                $QuoteVersion->updateStatusById($quoteVersion->id, config('const.quoteVersionStatus.val.editing'));
                // 承認データが存在すれば過去フラグを立てる
                $ApprovalHeader->updatePastFlgByProcessId($processKbn, $processId, config('const.flg.on'));
            }
        }
    }

    /**
     * 指定した得意先の請求期間などの情報を返す
     * @param $customerId   得意先ID
     * @return $requestInfo オブジェクト
     */
    public function getStrictCurrentMonthRequestPeriod($customerId)
    {
        $Customer       = new Customer();
        $Requests       = new Requests();
        $Credited       = new Credited();

        $customerData   = $Customer->getChargeData($customerId);
        $requestInfo    = $Requests->getLatestData($customerId);
        $notColoseRequestMon     = $Requests->getNotColoseRequestMon($customerId);      // 請求書作成済の状態で締めていない請求データの請求年月を取得する


        $activeRequestFlg = false;
        if ($requestInfo !== null) {
            $requestInfo->before_id = $requestInfo->id;
            if ($requestInfo->status === config('const.requestStatus.val.unprocessed') || $requestInfo->status === config('const.requestStatus.val.complete')) {
                $activeRequestFlg = true;
            } else {
                // 生きている請求がない
                $requestInfo->id = 0;
                $requestInfo->discount_amount = 0;
                $requestEDay = new Carbon($requestInfo->request_e_day);
                $requestInfo->request_s_day = $requestEDay->addDay(1)->format('Y/m/d');     // 最新の売上期間の終了日の翌日を開始日にする
                $requestInfo->closing_day   = $customerData->closing_day;
                $requestInfo->update_at     = '';

                switch ($requestInfo->closing_day) {
                    case config('const.customerClosingDay.val.any_time'):
                    case config('const.customerClosingDay.val.month_end'):
                        if ($notColoseRequestMon !== null) {
                            // 同じ計上月を使う
                            $requestInfo->request_mon   = $notColoseRequestMon->request_mon;
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, 1);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->endOfMonth()->format('Y/m/d');              // 今月末
                        } else {
                            // 翌月の計上月
                            // 2020/11/30 ⇒ 2020/12/31
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, 1);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->addMonth(1)->endOfMonth()->format('Y/m/d'); // 翌月の月末
                            $requestInfo->request_mon   = (new Carbon($this->formatRequestMon($requestInfo->request_mon, false, 1)))->addMonth(1)->format('Ym');
                        }
                        break;
                    default:
                        if ($notColoseRequestMon !== null) {
                            // 同じ計上月を使う
                            $requestInfo->request_mon   = $notColoseRequestMon->request_mon;
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, $requestInfo->closing_day);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->addMonth(1)->format('Y/m/d');
                        } else {
                            // 翌月の計上月
                            // 2020/11/20 ⇒ 2020/12/20
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, $requestInfo->closing_day);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->addMonth(1)->format('Y/m/d');
                            $requestInfo->request_mon   = (new Carbon($this->formatRequestMon($requestInfo->request_mon, false, 1)))->addMonth(1)->format('Ym');
                        }
                        break;
                }
            }
        } else {
            $requestInfo                = $Requests;
            Common::initModelProperty($requestInfo);
            $tmpDateData    = $this->getCurrentMonthRequestPeriod($customerId);
            $requestInfo->request_mon   = $tmpDateData['request_mon'];
            $requestInfo->closing_day   = $tmpDateData['closing_day'];
            $requestInfo->request_s_day = $tmpDateData['request_s_day'];
            $requestInfo->request_e_day = $tmpDateData['request_e_day'];
        }

        // 請求データにはない回収サイトの情報を追加
        $requestInfo->collection_sight  = $customerData->collection_sight;
        $requestInfo->collection_day    = $customerData->collection_day;

        // 請求データを再表示しない場合は、得意先の情報をセット
        if (!$activeRequestFlg) {
            // 得意先の情報をセット
            $requestInfo->customer_id           = $customerData->id;
            $requestInfo->customer_name         = $customerData->customer_name;
            $requestInfo->be_request_e_day      = $requestInfo->request_e_day;
            $requestInfo->status                = config('const.requestStatus.val.unprocessed');

            // 請求書発送予定日を算出 売上終了日の3日後
            $requestInfo->shipment_at           = (new Carbon($requestInfo->request_e_day))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');

            // 入金予定日を算出
            $currentCollectionDay = '';
            switch (Common::nullorBlankToZero($requestInfo->collection_day)) {
                case config('const.customerCollectionDay.val.any_time'):
                case config('const.customerCollectionDay.val.month_end'):
                    $tmpCurrentCollectionDay    = $this->formatRequestMon($requestInfo->request_mon, false, 1);
                    if (Common::nullorBlankToZero($requestInfo->collection_sight) !== 0) {
                        // 改修サイトの月数だけ増やす
                        $tmpCurrentCollectionDay = (new Carbon($tmpCurrentCollectionDay))->addMonth($requestInfo->collection_sight)->format('Y/m/d');
                    }
                    $currentCollectionDay       = (new Carbon($tmpCurrentCollectionDay))->endOfMonth()->format('Y/m/d');              // 今月末
                    break;
                default:
                    $currentCollectionDay = $this->formatRequestMon($requestInfo->request_mon, false, $requestInfo->collection_day);
                    if (Common::nullorBlankToZero($requestInfo->collection_sight) !== 0) {
                        // 改修サイトの月数だけ増やす
                        $currentCollectionDay   = (new Carbon($currentCollectionDay))->addMonth($requestInfo->collection_sight)->format('Y/m/d');
                    }
                    break;
            }

            $requestInfo->expecteddeposit_at    = $currentCollectionDay;
        }

        // 得意先担当部門、担当者を再取得
        $requestInfo->charge_department_id  = $customerData->charge_department_id;
        $requestInfo->charge_department_name = $customerData->department_name;
        $requestInfo->charge_staff_id       = $customerData->charge_staff_id;
        $requestInfo->charge_staff_name     = $customerData->staff_name;

        // 請求月を日本語化
        $requestInfo->request_mon_text = '';
        $requestInfo->request_mon_text       = $this->formatRequestMon($requestInfo->request_mon, true);

        $NOW = Carbon::now();
        $requestSDay = new Carbon($requestInfo->request_s_day);
        $requestEDay = new Carbon($requestInfo->request_e_day);
        // 今日が開始日になっているか
        $requestInfo->is_sales_started = true;
        // if ($requestSDay->gt($NOW)) {
        //     // 今日が開始日になっていない
        //     $requestInfo->is_sales_started = false;
        // }

        // 一時削除された請求取得
        $requestInfo->is_temporary_deleted = false;
        $tmpDeleteRequestData = $Requests->getTemporaryDeleteRequestData($requestInfo->customer_id);
        if ($tmpDeleteRequestData !== null) {
            // 一時削除データ存在した場合は売上期間変更不可
            $requestInfo->is_temporary_deleted = true;
        }

        return $requestInfo;
    }

    /**
     * 指定した新規得意先の請求期間などの情報を返す
     * @param $customerId   得意先ID
     * @param $request_e_day   終了日
     * @return $requestInfo オブジェクト
     */
    public function getNewCustomerStrictCurrentMonthRequestPeriod($customerId, $request_e_day)
    {
        $Customer       = new Customer();
        $Requests       = new Requests();
        // $Credited       = new Credited();

        $customerData   = $Customer->getChargeData($customerId);
        $requestInfo    = $Requests->getLatestData($customerId);
        $notColoseRequestMon     = $Requests->getNotColoseRequestMon($customerId);      // 請求書作成済の状態で締めていない請求データの請求年月を取得する


        $activeRequestFlg = false;
        if ($requestInfo !== null) {
            if ($requestInfo->status === config('const.requestStatus.val.unprocessed') || $requestInfo->status === config('const.requestStatus.val.complete')) {
                $activeRequestFlg = true;
            } else {
                // 生きている請求がない
                $requestInfo->before_id = $requestInfo->id;
                $requestInfo->id = 0;
                $requestInfo->discount_amount = 0;
                $requestEDay = new Carbon($requestInfo->request_e_day);
                $requestInfo->request_s_day = $requestEDay->addDay(1)->format('Y/m/d');     // 最新の売上期間の終了日の翌日を開始日にする
                $requestInfo->closing_day   = $customerData->closing_day;
                $requestInfo->update_at     = '';

                switch ($requestInfo->closing_day) {
                    case config('const.customerClosingDay.val.any_time'):
                    case config('const.customerClosingDay.val.month_end'):
                        if ($notColoseRequestMon !== null) {
                            // 同じ計上月を使う
                            $requestInfo->request_mon   = $notColoseRequestMon->request_mon;
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, 1);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->endOfMonth()->format('Y/m/d');              // 今月末
                        } else {
                            // 翌月の計上月
                            // 2020/11/30 ⇒ 2020/12/31
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, 1);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->addMonth(1)->endOfMonth()->format('Y/m/d'); // 翌月の月末
                            $requestInfo->request_mon   = (new Carbon($this->formatRequestMon($requestInfo->request_mon, false, 1)))->addMonth(1)->format('Ym');
                        }
                        break;
                    default:
                        if ($notColoseRequestMon !== null) {
                            // 同じ計上月を使う
                            $requestInfo->request_mon   = $notColoseRequestMon->request_mon;
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, $requestInfo->closing_day);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->addMonth(1)->format('Y/m/d');
                        } else {
                            // 翌月の計上月
                            // 2020/11/20 ⇒ 2020/12/20
                            $tmpClosingDay              = $this->formatRequestMon($requestInfo->request_mon, false, $requestInfo->closing_day);
                            $requestInfo->request_e_day = (new Carbon($tmpClosingDay))->addMonth(2)->format('Y/m/d');
                            $requestInfo->request_mon   = (new Carbon($this->formatRequestMon($requestInfo->request_mon, false, 1)))->addMonth(1)->format('Ym');
                        }
                        break;
                }
            }
        } else {
            $requestInfo                = $Requests;
            Common::initModelProperty($requestInfo);
            $tmpDateData    = $this->getNewCustomerMonthRequestPeriod($customerId, $request_e_day);
            $requestInfo->request_mon   = $tmpDateData['request_mon'];
            $requestInfo->closing_day   = $tmpDateData['closing_day'];
            $requestInfo->request_s_day = $tmpDateData['request_s_day'];
            $requestInfo->request_e_day = $tmpDateData['request_e_day'];
        }

        // 請求データにはない回収サイトの情報を追加
        $requestInfo->collection_sight  = $customerData->collection_sight;
        $requestInfo->collection_day    = $customerData->collection_day;

        // 請求データを再表示しない場合は、得意先の情報をセット
        if (!$activeRequestFlg) {
            // 得意先の情報をセット
            $requestInfo->customer_id           = $customerData->id;
            $requestInfo->customer_name         = $customerData->customer_name;
            $requestInfo->be_request_e_day      = $requestInfo->request_e_day;
            $requestInfo->status                = config('const.requestStatus.val.unprocessed');

            // 請求書発送予定日を算出 売上終了日の3日後
            $requestInfo->shipment_at           = (new Carbon($requestInfo->request_e_day))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');

            // 入金予定日を算出
            $currentCollectionDay = '';
            switch (Common::nullorBlankToZero($requestInfo->collection_day)) {
                case config('const.customerCollectionDay.val.any_time'):
                case config('const.customerCollectionDay.val.month_end'):
                    $tmpCurrentCollectionDay    = $this->formatRequestMon($requestInfo->request_mon, false, 1);
                    if (Common::nullorBlankToZero($requestInfo->collection_sight) !== 0) {
                        // 改修サイトの月数だけ増やす
                        $tmpCurrentCollectionDay = (new Carbon($tmpCurrentCollectionDay))->addMonth($requestInfo->collection_sight)->format('Y/m/d');
                    }
                    $currentCollectionDay       = (new Carbon($tmpCurrentCollectionDay))->endOfMonth()->format('Y/m/d');              // 今月末
                    break;
                default:
                    $currentCollectionDay = $this->formatRequestMon($requestInfo->request_mon, false, $requestInfo->collection_day);
                    if (Common::nullorBlankToZero($requestInfo->collection_sight) !== 0) {
                        // 改修サイトの月数だけ増やす
                        $currentCollectionDay   = (new Carbon($currentCollectionDay))->addMonth($requestInfo->collection_sight)->format('Y/m/d');
                    }
                    break;
            }

            $requestInfo->expecteddeposit_at    = $currentCollectionDay;
        }
        
        // 得意先担当部門、担当者を再取得
        $requestInfo->charge_department_id  = $customerData->charge_department_id;
        $requestInfo->charge_department_name = $customerData->department_name;
        $requestInfo->charge_staff_id       = $customerData->charge_staff_id;
        $requestInfo->charge_staff_name     = $customerData->staff_name;

        // 請求月を日本語化
        $requestInfo->request_mon_text = '';
        $requestInfo->request_mon_text       = $this->formatRequestMon($requestInfo->request_mon, true);

        $NOW = Carbon::now();
        $requestSDay = new Carbon($requestInfo->request_s_day);
        $requestEDay = new Carbon($requestInfo->request_e_day);
        // 今日が開始日になっているか
        $requestInfo->is_sales_started = true;
        // if ($requestSDay->gt($NOW)) {
        //     // 今日が開始日になっていない
        //     $requestInfo->is_sales_started = false;
        // }
        
        // 一時削除された請求取得
        $requestInfo->is_temporary_deleted = false;
        $tmpDeleteRequestData = $Requests->getTemporaryDeleteRequestData($requestInfo->customer_id);
        if ($tmpDeleteRequestData !== null) {
            // 一時削除データ存在した場合は売上期間変更不可
            $requestInfo->is_temporary_deleted = true;
        }

        return $requestInfo;
    }
    

    /**
     * 請求年月を文字列に変換する
     * @param $requestMon   yyyyMM
     * @param $isJp         / 区切りか 日本語か
     * @param $closingDay   締め日
     */
    public function formatRequestMon($requestMon, $isJp = false, $closingDay = null)
    {
        $result = '';
        if ($isJp) {
            $result = substr($requestMon, 0, 4) . '年' . substr($requestMon, 4, 2) . '月';
        } else {
            $result = substr($requestMon, 0, 4) . '/' . substr($requestMon, 4, 2);
        }

        if ($closingDay !== null) {
            $closingDay = str_pad($closingDay, 2, 0, STR_PAD_LEFT);
            if ($isJp) {
                $result = $result . $closingDay . '日';
            } else {
                $result = $result . '/' . $closingDay;
            }
        }
        return $result;
    }

    /**
     * 売上期間の開始終了 と 締め日を返す
     * 過去の請求データを考慮しない
     * @param $customerId   得意先ID
     * @param $baseDate     基準としたい年月 nullの場合は現在日
     * @return $requestInfo 配列
     */
    public function getCurrentMonthRequestPeriod($customerId, $baseDate = null)
    {
        $result         = [
            'request_mon'   => '',
            'closing_day'   => '',
            'request_s_day' => '',
            'request_e_day' => '',
        ];

        $Customer       = new Customer();
        $customerData   = $Customer->getById($customerId);
        $closingDay     = intval($customerData->closing_day);

        if ($baseDate === null) {
            $NOW = Carbon::now();
        } else {
            $NOW = new Carbon($baseDate);
        }

        switch ($closingDay) {
            case config('const.customerClosingDay.val.any_time'):
            case config('const.customerClosingDay.val.month_end'):
                $result['request_s_day']    = $NOW->format('Y/m') . '/01';
                $nowEndOfMonth  = $NOW->endOfMonth();
                $result['request_e_day']    = $nowEndOfMonth->format('Y/m/d');
                break;
            default:
                $day = $NOW->day;
                if ($closingDay >= $day) {
                    // 締め日が今月
                    $lastMonth                  = $baseDate === null ? Carbon::now()->subMonthNoOverflow() : (new Carbon($baseDate))->subMonthNoOverflow();
                    $lastMonthCloseDay          = $lastMonth->year . '/' . str_pad($lastMonth->month, 2, 0, STR_PAD_LEFT) . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                    $result['request_s_day']    = (new Carbon($lastMonthCloseDay))->addDay(1)->format('Y/m/d');
                    $result['request_e_day']    = $NOW->format('Y/m') . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                } else {
                    // 締め日が翌月
                    $nextMonth                  = $baseDate === null ? Carbon::now()->addMonthNoOverflow() : (new Carbon($baseDate))->addMonthNoOverflow();
                    $nextMonthCloseDay          = $nextMonth->year . '/' . str_pad($nextMonth->month, 2, 0, STR_PAD_LEFT) . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                    $nextMonthCloseDay          = (new Carbon($nextMonthCloseDay));
                    $lastMonthCloseDay          = $NOW->year . '/' . str_pad($NOW->month, 2, 0, STR_PAD_LEFT) . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                    $lastMonthCloseDay          = (new Carbon($lastMonthCloseDay));
                    $result['request_s_day']    = $lastMonthCloseDay->addDay(1)->format('Y/m/d');
                    $result['request_e_day']    = $nextMonthCloseDay->format('Y/m/d');
                }

                break;
        }
        $result['request_mon'] = (new Carbon($result['request_e_day']))->format('Ym');
        $result['closing_day'] = $closingDay;
        return $result;
    }

    /**
     * 売上終了日をもとに売上開始日、締め日を返す
     * 新規得意先に限る (過去の請求データが存在しない)
     * @param $customerId   得意先ID
     * @param $request_e_day   得意先ID
     * @param $baseDate     基準としたい年月 nullの場合は現在日
     * @return $requestInfo 配列
     */
    public function getNewCustomerMonthRequestPeriod($customerId, $request_e_day)
    {
        $result         = [
            'request_mon'   => '',
            'closing_day'   => '',
            'request_s_day' => '',
            'request_e_day' => $request_e_day,
        ];

        $Customer       = new Customer();
        $customerData   = $Customer->getById($customerId);
        $closingDay     = intval($customerData->closing_day);

        if ($request_e_day !== null) {
            $NOW = new Carbon($request_e_day);
        }

        switch ($closingDay) {
            case config('const.customerClosingDay.val.any_time'):
            case config('const.customerClosingDay.val.month_end'):
                $result['request_s_day']    = $NOW->format('Y/m') . '/01';
                // $nowEndOfMonth  = $NOW->endOfMonth();
                // $result['request_e_day']    = $nowEndOfMonth->format('Y/m/d');
                $result['request_mon'] = (new Carbon($result['request_e_day']))->format('Ym');
                break;
            default:
                $day = $NOW->day;
                if ($closingDay >= $day) {
                    // 締め日が売上終了日の月
                    $lastMonth                  = (new Carbon($request_e_day))->subMonthNoOverflow();
                    $lastMonthCloseDay          = $lastMonth->year . '/' . str_pad($lastMonth->month, 2, 0, STR_PAD_LEFT) . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                    $result['request_s_day']    = (new Carbon($lastMonthCloseDay))->addDay(1)->format('Y/m/d');
                    // $result['request_e_day']    = $NOW->format('Y/m') . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);

                    $result['request_mon'] = (new Carbon($result['request_e_day']))->format('Ym');
                } else {
                    // 締め日が売上終了日の翌月
                    $nextMonth                  = (new Carbon($request_e_day))->addMonthNoOverflow();
                    $nextMonthCloseDay          = $nextMonth->year . '/' . str_pad($nextMonth->month, 2, 0, STR_PAD_LEFT) . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                    $nextMonthCloseDay          = (new Carbon($nextMonthCloseDay));
                    $lastMonthCloseDay          = $NOW->year . '/' . str_pad($NOW->month, 2, 0, STR_PAD_LEFT) . '/' . str_pad($closingDay, 2, 0, STR_PAD_LEFT);
                    $lastMonthCloseDay          = (new Carbon($lastMonthCloseDay));
                    $result['request_s_day']    = $lastMonthCloseDay->addDay(1)->format('Y/m/d');
                    // $result['request_e_day']    = $nextMonthCloseDay->format('Y/m/d');
                    // $result['request_mon'] = $lastMonthCloseDay->addDay(1)->format('m') == $NOW->month ? (new Carbon($nextMonthCloseDay))->addMonthNoOverflow()->format('Ym') : (new Carbon($nextMonthCloseDay))->format('Ym');
                    $result['request_mon'] = (new Carbon($nextMonthCloseDay))->format('Ym');
                }

                break;
        }
        // $result['request_mon'] = (new Carbon($result['request_e_day']))->format('Ym');
        $result['closing_day'] = $closingDay;
        return $result;
    }

    /**
     * 売上データの承認ステータスを変更する
     * @param $isApprove
     * @param $salesData    対象の売上行
     * @param $requestId    請求ID
     * @param $customerName 得意先名(通知用)
     * @param $matterName   案件名(通知用)
     * @param $matterDepartmentId   案件の部門名
     * @param $parentDepartmentId   親部門ID
     * @param $redirectUrl          リダイレクト先URL(通知用)
     * @param $noticeData           通知データの配列(参照)
     */
    public function changeSalesStatus($isApprove, $salesData, $requestId, $customerName, $matterName, $matterDepartmentId, $parentDepartmentId, $redirectUrl, &$noticeData)
    {
        $result = [
            'sales_status'          => 0,
            'bk_sales_unit_price'   => 0,
            'sales_unit_price'      => 0,
            'chief_staff_id'        => 0,
        ];

        $Sales          = new Sales();
        $Department     = new Department();
        $SalesDetail    = new SalesDetail();

        // 承認日
        $NOW            = Carbon::now();

        $updateData = [];
        $noticeKey = '';
        $noticeStaffId = 0;
        $noticeContent = '';
        if (Common::isFlgOn($isApprove)) {
            // 承認した
            // 親部門がないため承認済みへ
            if (Common::nullorBlankToZero($parentDepartmentId) === 0) {

                $updateData['bk_sales_unit_price']  = $salesData->sales_unit_price;
                $updateData['status']               = config('const.salesStatus.val.approved'); // 承認済み
                $updateData['status_dep']           = $matterDepartmentId;   // 案件の部門に戻す
                $updateData['approval_staff_id']    = Auth::user()->id; // 承認者
                $updateData['approval_date']        = $NOW;             // 承認日
                $Sales->updateById($salesData->id, $updateData);


                // 通知データ
                $noticeStaffId                      = $salesData->apply_staff_id;    // 申請者に通知をする
                $noticeContent                      = str_replace('$matter_name', $matterName, str_replace('$customer_name', $customerName, config('message.notice.sales_approved')));
                $noticeKey = $updateData['status'] . '_' . $noticeStaffId;

                // 戻り値
                $result['sales_status']             = $updateData['status'];
                $result['bk_sales_unit_price']      = $updateData['bk_sales_unit_price'];
                $result['sales_unit_price']         = $salesData->sales_unit_price;         // 未使用
                $result['chief_staff_id']           = 0;                                    // 未使用
            } else {
                $updateData['status']               = config('const.salesStatus.val.applying'); // 申請中(変更無し)
                $updateData['status_dep']           = $parentDepartmentId;              // 親部門
                $updateData['approval_staff_id']    = Auth::user()->id; // 承認者
                $updateData['approval_date']        = $NOW;             // 承認日
                $Sales->updateById($salesData->id, $updateData);

                // 通知データ
                $parentDepartmentData               = $Department->getById($parentDepartmentId);
                $noticeStaffId                      = $parentDepartmentData->chief_staff_id;
                $noticeContent                      = str_replace('$matter_name', $matterName, str_replace('$customer_name', $customerName, config('message.notice.sales_applying')));
                $noticeKey = $updateData['status'] . '_' . $noticeStaffId;

                // 戻り値
                $result['sales_status']             = $updateData['status'];
                $result['bk_sales_unit_price']      = $salesData->bk_sales_unit_price;      // 未使用
                $result['sales_unit_price']         = $salesData->sales_unit_price;         // 未使用
                $result['chief_staff_id']           = $parentDepartmentData->chief_staff_id;
            }
        } else {
            // 否認した
            // 売上明細の単価を更新し販売総額を取得する
            $totalPrice = $SalesDetail->updateSalesUnitPrice($requestId, $salesData->id, $salesData->bk_sales_unit_price);

            $updateData['sales_unit_price']         = $salesData->bk_sales_unit_price;                  // 変更前の販売単価に戻す
            $updateData['sales_makeup_rate']        = $this->calcRate($updateData['sales_unit_price'], $salesData->regular_price);        // 販売掛率
            $updateData['profit_total']             = $this->calcProfitTotal($totalPrice['sales_total'], $totalPrice['cost_total']);      // 粗利額
            $updateData['gross_profit_rate']        = $this->calcRate($updateData['profit_total'], $totalPrice['sales_total']);           // 粗利率

            $updateData['status']                   = config('const.salesStatus.val.not_applying');     // 未申請
            $updateData['status_dep']               = $matterDepartmentId;                              // 案件の部門に戻す
            $Sales->updateById($salesData->id, $updateData);

            // 通知データ
            $noticeStaffId                          = $salesData->apply_staff_id;    // 申請者に通知をする
            $noticeContent                          = str_replace('$matter_name', $matterName, str_replace('$customer_name', $customerName, config('message.notice.sales_sendback')));
            $noticeKey = $updateData['status'] . '_' . $noticeStaffId;

            // 戻り値
            $result['sales_status']                 = $updateData['status'];
            $result['bk_sales_unit_price']          = $salesData->bk_sales_unit_price;  // 未使用
            $result['sales_unit_price']             = $updateData['sales_unit_price'];
            $result['chief_staff_id']               = 0;                                // 未使用
        }

        // 通知データをセット
        if (!isset($noticeData[$noticeKey])) {
            $noticeData[$noticeKey]['notice_flg']   = config('const.flg.off');
            $noticeData[$noticeKey]['staff_id']     = $noticeStaffId;
            $noticeData[$noticeKey]['content']      = $noticeContent;
            $noticeData[$noticeKey]['redirect_url'] = $redirectUrl;
        }


        return $result;
    }

    /**
     * CSVファイルを配列にして返す
     * @param $file         ファイル名 または 「Illuminate\Http\UploadedFile」
     * @param $fromEncoding ファイルの文字コード
     * @param $toEncoding   変換後の文字コード
     */
    public function csvToArray($file, $fromEncoding = 'SJIS-win', $toEncoding = 'UTF-8')
    {
        $csv = [];
        $tempFile = null;
        try {
            $fromFile = file_get_contents($file);
            $toFile = mb_convert_encoding($fromFile, $toEncoding, $fromEncoding);
            $tempFile = tmpfile();

            fwrite($tempFile, $toFile);
            rewind($tempFile);

            while (($data = fgetcsv($tempFile))) {
                $csv[] = $data;
            }
        } catch (\Exception $e) {
            Log::error($e);
        } finally {
            if ($tempFile !== false && $tempFile !== null) {
                fclose($tempFile);
            }
        }
        return $csv;
    }

    
    /**
     * 案件完了
     *
     * @return void
     */
    public function matterComplete($matterId)
    {
        $result = ['status' => false, 'msg' => ''];

        $Matter = new Matter();
        $Order = new Order();
        $Reserve = new Reserve();
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        $ProductStockShelf = new ProductstockShelf();
        $Loading = new Loading();
        $Requests = new Requests();
        $SalesDetail = new SalesDetail();

        try {

            $matter = $Matter->getById($matterId);
            if (is_null($matter)) {
                throw new \Exception(config('message.error.error').':'.$matterId);
            }

            do {

                // 未入荷の発注済みデータの存在チェック
                if ($Order->hasNotArrivalByMatter($matter->matter_no)) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.matter_detail.complete_order');
                    break;
                }

                // 案件在庫（発注品の在庫）の存在チェック
                if ($ProductStockShelf->hasMatterStock($matter->id)) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.matter_detail.complete_stock');
                    break;
                }

                // 積込中データの存在チェック
                if ($Loading->hasLoadingByMatter($matter->id)) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.matter_detail.complete_loading');
                    break;
                }

                // 売上に紐付かない納品・返品データの存在チェック
                $unSalesData = $SalesDetail->getSchedulerSalesForMatterDetail($matter->id);
                if (count($unSalesData) > 0) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.matter_detail.complete_sales');
                    break;
                }

                // 売上確定していない売上明細の存在チェック
                if ($SalesDetail->hasNotSalesByMatter($matter->id)) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.matter_detail.complete_sales');
                    break;
                }

                // 請求締めされていない売上明細データの存在チェック
                if (!$Requests->isValidCompleteMatter($matter->customer_id, $matter->id)) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.matter_detail.complete_requests');
                    break;
                }

                // 引当を解除する(在庫品引当・預かり品引当)
                $Reserve->cancelReserveByMatterId($matter->id);

                // 未処理の出荷指示データを削除
                $shipmentData = $Shipment->getByMatterId($matter->id, ['loading_finish_flg' => config('const.flg.off')]);
                $shipmentIdList = $shipmentData->pluck('id')->toArray();

                $Shipment->physicalDeleteByIdList($shipmentIdList);
                $ShipmentDetail->physicalDeleteByShipmentIdList($shipmentIdList);
                
                // 案件を完了へ更新
                $matterParams = ['id' => $matter->id, 'complete_flg' => config('const.flg.on')];
                $Matter->updateByIdEx($matterParams);

                $result['status'] = true;
            } while (false);

        } catch (\Exception $e) {
            Log::error($e);
            $result['status'] = false;
        }

        return $result;
    }
}

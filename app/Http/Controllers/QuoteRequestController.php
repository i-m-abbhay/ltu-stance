<?php
namespace App\Http\Controllers;

use DB;
use Storage;
use Session;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\General;
use App\Models\Construction;
use App\Models\Matter;
use App\Models\Customer;
use App\Models\SpecItemHeader;
use App\Models\Choice;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestDetail;
use App\Models\NumberManage;
use App\Models\SpecDefault;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Notice;
use App\Models\NoticeSetting;
use App\Models\CalendarData;
use App\Libs\Common;
use App\Libs\SystemUtil;

/**
 * 見積依頼入力
 */
class QuoteRequestController extends Controller
{
    // 仕様項目シンボル(定数)
    const SYMBOL_STR = '@@@';
    // 結合文字(定数)
    const JOIN_STR = '___';
    // 可変項目用(定数)　項目名分割数でinput_value01～input_value03を判断。
    const VAL01_CNT = 3;
    const VAL02_CNT = 4;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 初期表示
     *
     * @param Request $request
     * @param int $id 見積異依頼ID
     * @return void
     */
    public function index(Request $request, $id = null)
    {
        $Matter = new Matter();
        $QuoteRequest = new QuoteRequest();
        $General = new General();
        $Construction = new Construction();
        $Customer = new Customer();
        $SpecItemHeader = new SpecItemHeader();
        $Choice = new Choice();
        $CalendarData = new CalendarData();

        // 見積依頼項目リスト取得
        $quoteReqList = $Construction->getQreqData(config('const.flg.on'));
        
        // ===== メインデータ取得 =====
        $fileNameList = [];
        $requestedFlg = false;
        $mainData = null;
        $templates = null;
        if (is_null($id)) {
            // 新規
            $mainData = $Matter;
            $subData = $QuoteRequest;
            Common::initModelProperty($mainData);
            Common::initModelProperty($subData);
            $mainData = collect($mainData);
            $subData = collect($subData);
            $mainData->concat($subData);

            // 初期値セット
            // 見積提出期限
            // $mainData['quote_limit_date'] = Carbon::today()->addDays(config('const.quoteLimitInit'))->format('Y/m/d');
            $limitData = $CalendarData->getAfterBusinessDay(config('const.quoteLimitInit'), Carbon::today()->format('Y/m/d'));
            if (is_null($limitData)) {
                // カレンダーデータが存在しなかった場合
                $mainData['quote_limit_date'] = Carbon::today()->format('Y/m/d');
                Session::flash('flash_error', config('message.error.quote_request.calendar_none'));
            } else {
                $mainData['quote_limit_date'] = $limitData->calendar_date;
            }
            // 工務店標準仕様フラグ
            $mainData['builder_standard_flg'] = config('const.flg.off');
            // 見積参照利用フラグ
            $mainData['use_ref_quote_flg'] = config('const.flg.off');
            $mainData['ref_matter_no'] = null;
            // 省令準耐火地域フラグ
            // $mainData['semi_fireproof_area_flg'] = config('const.flg.off');
            // 得意先ID
            $mainData['customer_id'] = null;

            $templates = $SpecItemHeader;
        } else {
            // 編集
            $id = (int)$id;
            // データ取得
            $mainData = $QuoteRequest->getQuoteRequest($id);
            if (is_null($mainData)) {
                // データなし
                throw new NotFoundHttpException();
            }

            // 見積依頼項目・見積依頼済み項目 データ整形
            $mainData->quote_request_kbn_arr = $this->castArrayFromJsonArr($mainData['quote_request_kbn']);
            $mainData->quote_requested_kbn_arr = $this->castArrayFromJsonArr($mainData['quote_requested_kbn']);

            // 添付ファイル取得
            $mainData->file_list = [];
            // フォルダ内のファイル全取得
            $fileList = Storage::files(config('const.uploadPath.quote_request').$id);
            foreach ($fileList as $file) {
                $tmps = explode('/', $file);
                $fileName = $tmps[count($tmps) - 1];

                $fileNameList[] = $fileName;
            }
            $mainData->file_list = $fileNameList;

            if ($mainData['status'] === config('const.quoteRequestStatus.val.requested')) {
                // 見積依頼済みの場合
                $requestedFlg = true;
                // 見積依頼明細取得
                $qreqDataList = $QuoteRequest->getQuoteRequestDetail($id);
                // 仕様項目データを取得する
                $templates = $this->getTemplates($qreqDataList, $mainData->quote_request_kbn_arr);
            } else {
                $templates = $SpecItemHeader;
            }

        }

        // ===== STEP1用のデータ取得 =====
        $customerList = null;
        $ownerList = null;
        $architectureList = null;
        $specList = null;
        $matterList = null;
        if (!$requestedFlg) {
            // 得意先名リスト取得
            $customerList = $Customer->getComboList();
            // 施主名リスト取得
            $ownerList = $Matter->getOwnerList();
            // 建築種別リスト取得
            $architectureList = $General->getCategoryList(config('const.general.arch'));
            // 仕様区分リスト取得
            $specList = $General->getCategoryList(config('const.general.spec'));
            // 案件リスト取得
            $matterList = $Matter->getQuoteExistList();
        }

        // ===== STEP2用のデータ取得 =====
        // 選択肢データ取得
        $choiceList = $Choice->getAllData();
        // 選択肢画像取得（バイナリ変換して出力する）
        foreach ($choiceList as &$choice) {
            if (!empty($choice['image_path'])) {
                $file = config('const.uploadPath.item_choice').$choice['image_path'];
                $fileInfo = pathinfo($file);
                if(in_array($fileInfo['extension'], config('const.extEncode.png'))){
                    $choice['image_path'] = config('const.encode.png').base64_encode(Storage::get($file));
                }
                if(in_array($fileInfo['extension'], config('const.extEncode.jpeg'))){
                    $choice['image_path'] = config('const.encode.jpeg').base64_encode(Storage::get($file));
                }
            }
        }

        if (!$requestedFlg) {
            // 新規 or 作成中の場合、見積依頼入力を開く
            return view('QuoteRequest.quote-request')
                    ->with('mainData', $mainData)
                    ->with('customerList', $customerList)
                    ->with('ownerList', $ownerList)
                    ->with('architectureList', $architectureList)
                    ->with('specList', $specList)
                    ->with('quoteReqList', $quoteReqList)
                    ->with('matterList', $matterList)
                    ->with('templates', $templates)
                    ->with('choiceList', $choiceList)
                    ;
        } else {
            // 見積依頼済みの場合、見積依頼詳細を開く
            return view('QuoteRequest.quote-request-detail')
                    ->with('mainData', $mainData)
                    ->with('quoteReqList', $quoteReqList)
                    ->with('qreqDataList', $qreqDataList)
                    ->with('templates', $templates)
                    ->with('choiceList', $choiceList)
                    ;
        }
    }

    /**
     * 案件データ取得
     *
     * @param Request $request
     * @return void
     */
    public function getMatter(Request $request) {
        $resultSts = array(
            'status' => false, 
            'matter_data' => null, 
            'matter_name' => null, 
            'builder_standard_data' => null, 
            'ref_qreq_data' => null,
            'qreq_data' => null,
            'templates' => null
        );

        $SystemUtil = new SystemUtil();
        $QuoteRequest = new QuoteRequest();
        $Matter = new Matter();
        $Quote = new Quote();
        $SpecDefault = new SpecDefault();
        $SpecItemHeader = new SpecItemHeader();

        // リクエストデータ取得
        $params = $request->request->all();

        // バリデーションチェック
        $this->isValidStep1($request);

        // 見積依頼項目を配列キャスト
        $qreqKbn = json_decode($params['quote_request_kbn']);

        $refQuoteRequestKbn = [];
        $matterName = '';
        $matterList = null;
        $qreqDataList = null;
        $templates = null;
        $fileList = [];
        if (!empty($params['quote_request_id'])) {
            // 見積依頼を保存済みの場合
            $qreqDataList = $QuoteRequest->getQuoteRequestDetail($params['quote_request_id']);
            if (count($qreqDataList) > 0) {
                // 仕様項目データを取得する
                $templates = $this->getTemplates($qreqDataList, $qreqKbn);
            }
        } else {
            // 新規入力の場合

            // 案件名生成
            $matterName = $SystemUtil->createMatterName($params['owner_name'], null, $params['customer_id']);
            // 案件データ取得
            $matterList = $Matter->getCombiComboList($params['customer_id'], $params['owner_name'], config('const.flg.off'));

            // 工務店標準仕様
            if ($params['builder_standard_flg'] == config('const.flg.on')) {
                $qreqDataList = $SpecDefault->getByCustomerId($params['customer_id']);
                
                // 仕様項目データを取得する
                $templates = $this->getTemplates($qreqDataList, $qreqKbn);

                // 添付ファイル取得
                $fileNameList = [];
                // フォルダ内のファイル全取得
                $fileList = Storage::files(config('const.uploadPath.quote_request_default').$params['customer_id']);
                foreach ($fileList as $file) {
                    $tmps = explode('/', $file);
                    $fileName = $tmps[count($tmps) - 1];

                    $fileNameList[] = $fileName;
                }
                $fileList = $fileNameList;
            }
            // 見積依頼参照
            if ($params['use_ref_quote_flg'] == config('const.flg.on')) {
                if (!empty($params['ref_matter_no'])) {
                    $qreqDataList = $Matter->getQuoteRequestDetail($params['ref_matter_no']);
                } else if (!empty($params['ref_quote_no'])) {
                    $qreqDataList = $Quote->getQuoteRequestDetail($params['ref_quote_no']);
                }

                if ($qreqDataList != null) {
                    $qreqDataList = $qreqDataList->toArray();
                }
                // 同じ見積依頼項目が存在する場合最新の物を使う
                for ($i = 0; $i < count($qreqDataList); $i++) {
                    for ($j = 1; $j < count($qreqDataList); $j++) {
                        if ($qreqDataList[$i]['quote_request_id'] != $qreqDataList[$j]['quote_request_id']
                            && $qreqDataList[$i]['quote_request_kbn'] == $qreqDataList[$j]['quote_request_kbn'])
                        {
                            if ($qreqDataList[$i]['quote_request_id'] < $qreqDataList[$j]['quote_request_id']) {
                                array_splice($qreqDataList, $i, 1);
                            } else {
                                array_splice($qreqDataList, $j, 1);
                            }
                        }
                    }
                }

                // 見積依頼項目取得
                foreach ($qreqDataList as $qreq) {
                    if (!in_array($qreq['quote_request_kbn'], $refQuoteRequestKbn)) {
                        $refQuoteRequestKbn[] = $qreq['quote_request_kbn'];
                    }
                    
                }
                
                // 仕様項目データを取得する
                $templates = $this->getTemplates($qreqDataList, $refQuoteRequestKbn);
            }
        }
        
        if (is_null($templates)) {
            $templates = $SpecItemHeader->getQuoteRequestTemplateData();
        }

        $resultSts['status'] = true;
        $resultSts['matter_data'] = $matterList;
        $resultSts['matter_name'] = $matterName;
        $resultSts['qreq_data'] = $qreqDataList;
        $resultSts['ref_quote_request_kbn'] = $refQuoteRequestKbn;
        $resultSts['templates'] = $templates;
        $resultSts['file_list'] = $fileList;

        return \Response::json($resultSts);
    }
    
    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = array('status' => false, 'data' => null);

        // リクエストデータ取得
        $params = $request->request->all();

        // 見積提出期限チェック
        $this->isValidLimitDate($request);
        // 添付ファイル
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        // 新規or更新
        $newFlg = false;
        if (empty($params['quote_request_id'])) {
            $newFlg = true;
        }

        $Notice = new Notice();
        $NoticeSetting = new NoticeSetting();
        DB::beginTransaction();
        try {
            $noticeContent = '';
            if ($newFlg) {
                // 登録
                $resultParams = $this->saveNew($params, config('const.flg.off'));

                // 工務店標準の場合、ファイルコピー
                if ($params['builder_standard_flg'] == config('const.flg.on')) {
                    // ファイルコピー
                    $copyFileList = Storage::files(config('const.uploadPath.quote_request_default').$params['customer_id']);
                    foreach ($copyFileList as $file) {
                        $tmps = explode('/', $file);
                        $fileName = $tmps[count($tmps) - 1];
                        $newFilePath = config('const.uploadPath.quote_request').$resultParams['quote_request_id'].'/'.$fileName;
                        Storage::copy($file, $newFilePath);
                    }

                    // ファイル削除
                    $deleteList = [];
                    if (!is_null($params['delete_files'])) {
                        $deleteFiles = $this->convArr($params['delete_files']);
                        foreach ($deleteFiles as $delFile) {
                            $deleteList[] = config('const.uploadPath.quote_request').$resultParams['quote_request_id'].'/'.$delFile;
                        }
                        Storage::delete($deleteList);
                    }
                }

                // ファイルアップロード
                foreach ($uploadFileList as $fileNm => $fileObj) {
                    $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request').$resultParams['quote_request_id'], $fileObj->getClientOriginalName());
                }

                $noticeContent = config('message.notice.quote_request_new').$resultParams['matter_name'];
            } else {
                // 更新
                $resultParams = $this->saveUpdate($params, config('const.flg.off'));

                // ファイル削除
                if (!is_null($params['delete_files'])) {
                    $deleteList = [];
                    $deleteFiles = $this->convArr($params['delete_files']);
                    foreach ($deleteFiles as $delFile) {
                        $deleteList[] = config('const.uploadPath.quote_request').$resultParams['quote_request_id'].'/'.$delFile;
                    }
                    Storage::delete($deleteList);
                }

                // ファイルアップロード
                foreach ($uploadFileList as $fileNm => $fileObj) {
                    $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request').$resultParams['quote_request_id'], $fileObj->getClientOriginalName());
                }

                $noticeContent = config('message.notice.quote_request_edit').$resultParams['matter_name'];
            }
            
            // 通知対象者取得
            $noticeToStaffList = [];
            $kbnList = $this->convArr($params['quote_request']);
            foreach ($kbnList as $kbn) {
                $noticeSettingList = $NoticeSetting->getToStaffList(config('const.notice_type.quote_request'), config('const.notice_from_all'), Carbon::today(), $kbn);
                foreach ($noticeSettingList as $noticeSetting) {
                    if (!in_array($noticeSetting['to_staff_id'], $noticeToStaffList)) {
                        $noticeToStaffList[] = $noticeSetting['to_staff_id'];
                    }
                }
            }
            // 通知データ作成
            $noticeParams = [];
            $noticeRow = [];
            $noticeRow['notice_flg'] = config('const.flg.off');
            $noticeRow['content'] = $noticeContent;
            $noticeRow['redirect_url'] = '/quote-request-edit/'.$resultParams['quote_request_id'];
            foreach ($noticeToStaffList as $staffId) {
                $noticeRow['staff_id'] = $staffId;
                $noticeParams[] = $noticeRow;
            }
            // 通知登録
            $noticeResult = $Notice->addList($noticeParams);
            if (!$noticeResult) {
                throw new \Exception(config('message.error.save'));
                
            }

            DB::commit();

            $resultSts['status'] = true;
            $resultSts['data'] = $resultParams;
            // Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts['status'] = false;
            Log::error($e);
            // Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
    
    /**
     * 一時保存
     *
     * @param Request $request
     * @return void
     */
    public function saveDraft(Request $request)
    {
        $result = array('status' => false, 'data' => null);

        // リクエストデータ取得
        $params = $request->request->all();

        // 添付ファイル
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        // 新規or更新
        $newFlg = false;
        if (empty($params['quote_request_id'])) {
            $newFlg = true;
        }

        DB::beginTransaction();
        try {
            if ($newFlg) {
                // 登録
                $resultParams = $this->saveNew($params, config('const.flg.on'));
                
                // 工務店標準の場合、ファイルコピー
                if ($params['builder_standard_flg'] == config('const.flg.on')) {
                    // ファイルコピー
                    $copyFileList = Storage::files(config('const.uploadPath.quote_request_default').$params['customer_id']);
                    foreach ($copyFileList as $file) {
                        $tmps = explode('/', $file);
                        $fileName = $tmps[count($tmps) - 1];
                        $newFilePath = config('const.uploadPath.quote_request').$resultParams['quote_request_id'].'/'.$fileName;
                        Storage::copy($file, $newFilePath);
                    }

                    // ファイル削除
                    $deleteList = [];
                    if (!is_null($params['delete_files'])) {
                        $deleteFiles = $this->convArr($params['delete_files']);
                        foreach ($deleteFiles as $delFile) {
                            $deleteList[] = config('const.uploadPath.quote_request').$resultParams['quote_request_id'].'/'.$delFile;
                        }
                        Storage::delete($deleteList);
                    }
                }
                // ファイルアップロード
                foreach ($uploadFileList as $fileNm => $fileObj) {
                    $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request').$resultParams['quote_request_id'], $fileObj->getClientOriginalName());
                }


            } else {
                // 更新
                $resultParams = $this->saveUpdate($params, config('const.flg.on'));
                
                // ファイル削除
                if (!is_null($params['delete_files'])) {
                    $deleteList = [];
                    $deleteFiles = $this->convArr($params['delete_files']);
                    foreach ($deleteFiles as $delFile) {
                        $deleteList[] = config('const.uploadPath.quote_request').$resultParams['quote_request_id'].'/'.$delFile;
                    }
                    Storage::delete($deleteList);
                }

                // ファイルアップロード
                foreach ($uploadFileList as $fileNm => $fileObj) {
                    $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request').$resultParams['quote_request_id'], $fileObj->getClientOriginalName());
                }
            }
            
            DB::commit();
            $result['status'] = true;
            $result['data'] = $resultParams;
            // Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            // Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }

    /**
     * 工務店標準として登録
     *
     * @param Request $request
     * @return void
     */
    public function saveDefault(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();

        // 添付ファイル
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        DB::beginTransaction();
        try {
            $SpecDefault = new SpecDefault();

            // 顧客ID
            $customerId = $params['customer_id'];
            // 見積依頼区分
            $quoteRequestKbns = json_decode($params['quote_request_kbn']);

            // 見積依頼明細
            $specDataList = [];
            $tmpKeyArr = [];
            foreach ($params as $key => $val) {
                if (strpos($key, self::SYMBOL_STR) === 0) {
                    $tmpKeyArr[str_replace(self::SYMBOL_STR, '', $key)] = $val;
                }
            }
            $cnt = 0;
            $tmpKeyNameArr = [];
            foreach ($tmpKeyArr as $key => $val) {
                $tmpArr = explode(self::JOIN_STR, $key);
                if (count($tmpArr) >= self::VAL01_CNT) {
                    // 見積依頼項目・仕様項目ヘッダID・仕様項目明細IDごとに1レコードにまとめる
                    $keyName = $tmpArr[0].self::JOIN_STR.$tmpArr[1].self::JOIN_STR.$tmpArr[2];
                    if (isset($tmpKeyNameArr[$keyName])) {
                        switch(count($tmpArr)) {
                            case self::VAL01_CNT:
                                $specDataList[$tmpKeyNameArr[$keyName]]['input_value01'] = $val;
                                break;
                            case self::VAL02_CNT:
                                $specDataList[$tmpKeyNameArr[$keyName]]['input_value02'] = $val;
                                break;
                        }
                    } else {
                        $specDataList[$cnt]['quote_request_kbn'] = $tmpArr[0];
                        $specDataList[$cnt]['spec_item_header_id'] = $tmpArr[1];
                        $specDataList[$cnt]['spec_item_detail_id'] = $tmpArr[2];
                        switch(count($tmpArr)) {
                            case self::VAL01_CNT:
                                $specDataList[$cnt]['input_value01'] = $val;
                                break;
                            case self::VAL02_CNT:
                                $specDataList[$cnt]['input_value02'] = $val;
                                break;
                        }

                        $tmpKeyNameArr[$keyName] = $cnt;
                    }
                }
                $cnt++;
            }
            
            // 登録　※登録更新なしの場合でもエラーにしないように変更
            $saveResult = $SpecDefault->updateList($customerId, $quoteRequestKbns, $specDataList);

            if ($saveResult) {
                // ファイル操作の後にコミット

                if (empty($params['quote_request_id'])) {
                    // 見積依頼 新規
                    if ($params['builder_standard_flg'] == config('const.flg.on')) {
                        // 工務店標準
                        
                        // ファイル削除
                        $deleteList = [];
                        if (!is_null($params['delete_files'])) {
                            $deleteFiles = $this->convArr($params['delete_files']);
                            foreach ($deleteFiles as $delFile) {
                                $deleteList[] = config('const.uploadPath.quote_request_default').$customerId.'/'.$delFile;
                            }
                            Storage::delete($deleteList);
                        }
                        // ファイルアップロード
                        foreach ($uploadFileList as $fileNm => $fileObj) {
                            $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request_default').$customerId, $fileObj->getClientOriginalName());
                        }
                    } else if ($params['use_ref_quote_flg'] == config('const.flg.on')) {
                        // 見積依頼参照・・・元々画面に添付ファイルを引っ張ってきていないので一旦コピー対応ナシ
                        // $refQuoteRequestId = null;
                        // if (!empty($params['ref_matter_no'])) {
                        //     // 案件
                        // } else if (!empty($params['ref_quote_no'])) {
                        //     // 見積番号
                        // }
                        
                        // 一旦ファイル全削除
                        $allFileList = Storage::files(config('const.uploadPath.quote_request_default').$customerId);
                        Storage::delete($allFileList);
                        // ファイルアップロード
                        foreach ($uploadFileList as $fileNm => $fileObj) {
                            $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request_default').$customerId, $fileObj->getClientOriginalName());
                        }
                    } else {
                        // 新規入力

                        // 一旦ファイル全削除
                        $allFileList = Storage::files(config('const.uploadPath.quote_request_default').$customerId);
                        Storage::delete($allFileList);
                        // ファイルアップロード
                        foreach ($uploadFileList as $fileNm => $fileObj) {
                            $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request_default').$customerId, $fileObj->getClientOriginalName());
                        }
                    }
                } else {
                    // 見積依頼 編集（依頼作成中）

                    // 一旦ファイル全削除
                    $allFileList = Storage::files(config('const.uploadPath.quote_request_default').$customerId);
                    Storage::delete($allFileList);

                    // ファイルコピー
                    $copyFileList = Storage::files(config('const.uploadPath.quote_request').$params['quote_request_id']);
                    foreach ($copyFileList as $file) {
                        $tmps = explode('/', $file);
                        $fileName = $tmps[count($tmps) - 1];
                        $newFilePath = config('const.uploadPath.quote_request_default').$customerId.'/'.$fileName;
                        Storage::copy($file, $newFilePath);
                    }

                    // ファイル削除
                    $deleteList = [];
                    if (!is_null($params['delete_files'])) {
                        $deleteFiles = $this->convArr($params['delete_files']);
                        foreach ($deleteFiles as $delFile) {
                            $deleteList[] = config('const.uploadPath.quote_request_default').$customerId.'/'.$delFile;
                        }
                        Storage::delete($deleteList);
                    }
                    // ファイルアップロード
                    foreach ($uploadFileList as $fileNm => $fileObj) {
                        $request->file($fileNm)->storeAs(config('const.uploadPath.quote_request_default').$customerId, $fileObj->getClientOriginalName());
                    }
                }

                DB::commit();
                $resultSts = true;
                // Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            // Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 添付ファイル存在確認
     *
     * @param Request $request
     * @param int $id
     * @param string $fileName
     * @return void
     */
    public function existsFile(Request $request, $id, $fileName)
    {
        $fileName = urldecode($fileName);
        $result = Storage::exists(config('const.uploadPath.quote_request').$id.'/'.$fileName);
        return \Response::json($result);
    }

    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param int $id
     * @param string $fileName
     * @return void
     */
    public function download(Request $request, $id, $fileName)
    {
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.quote_request').$id.'/'.$fileName), $fileName, $headers);
        // return Storage::download(config('const.uploadPath.quote_request').$id.'/'.$fileName, $fileName, $headers);
    }

    /**
     * 添付ファイルダウンロード（工務店標準）
     *
     * @param Request $request
     * @param int $cutomerid
     * @param string $fileName
     * @return void
     */
    public function downloadDefault(Request $request, $cutomerid, $fileName)
    {
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.quote_request_default').$cutomerid.'/'.$fileName), $fileName, $headers);
    }

    /**
     * 見積依頼項目を削除
     *
     * @param Request $request
     * @return void
     */
    public function deleteRequestKbn(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();

        $QuoteRequest = new QuoteRequest();
        $QuoteRequestDetail = new QuoteRequestDetail();
        DB::beginTransaction();
        try {
            $delQreqKbnId = (int)$params['quote_request_kbn_id'];

            // 見積依頼データ取得
            $data = $QuoteRequest->getQuoteRequest($params['quote_request_id']);
            $requestKbnArr = $this->castArrayFromJsonArr($data['quote_request_kbn']);
            if (in_array($delQreqKbnId, $requestKbnArr)) {
                // 見積依頼区分から対象の見積依頼項目を削除
                $updateQreqItemsStr = '';
                foreach ($requestKbnArr as $qreqKbn) {
                    if ($qreqKbn === $delQreqKbnId) {
                        continue;
                    }

                    if ($updateQreqItemsStr != '') {
                        $updateQreqItemsStr .= ',';
                    }
                    $updateQreqItemsStr .= $qreqKbn;
                }
                $updateQreqItems = $this->formatJsonArr($updateQreqItemsStr);

                // 見積依頼データ更新
                $updateParams = [];
                foreach ($data->toArray() as $key => $val) {
                    $updateParams[$key] = $val;
                }
                $updateParams['quote_request_kbn'] = $updateQreqItems;
                $QuoteRequest->updateById($updateParams);

                // 見積依頼明細データを論理削除
                $QuoteRequestDetail->deleteByQuoteRequestKbn($params['quote_request_id'], $delQreqKbnId);
            }
            
            DB::commit();
            $resultSts = true;
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 見積依頼論理削除
     *
     * @param Request $request
     * @return void
     */
    public function deleteRequest(Request $request) 
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();

        $QuoteRequest = new QuoteRequest();
        $QuoteRequestDetail = new QuoteRequestDetail();
        DB::beginTransaction();
        try {
            $delRequestId = $params['quote_request_id'];

            $QuoteRequest->deleteById($delRequestId);
            $QuoteRequestDetail->deleteList($delRequestId);

            DB::commit();
            $resultSts = true;
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 新規登録処理
     *
     * @param array $params
     * @param int $draftFlg 一時保存フラグ
     * @return void
     */
    private function saveNew($params, $draftFlg) {
        $NumberManage = new NumberManage();
        $Matter = new Matter();
        $Construction = new Construction();
        $QuoteRequest = new QuoteRequest();
        $QuoteRequestDetail = new QuoteRequestDetail();
        $QuoteDetail = new QuoteDetail();
        $Customer = new Customer();

        // 案件
        if (empty($params['matter_no'])) {
            // 新規作成

            // 得意先データ取得
            $customerData = $Customer->find($params['customer_id']);

            $matterParams = [];
            $matterParams['matter_name'] = $params['matter_name'];
            $matterParams['matter_no'] = $NumberManage->getSeqNo(config('const.number_manage.kbn.matter'), Carbon::today()->format('Ym'));
            $matterParams['matter_create_date'] = Carbon::today()->format('Y/m/d');
            $matterParams['customer_id'] = $params['customer_id'];
            $matterParams['owner_name'] = $params['owner_name'];
            $matterParams['architecture_type'] = $params['architecture_type'];
            $matterParams['department_id'] = $customerData['charge_department_id'];
            $matterParams['staff_id'] = $customerData['charge_staff_id'];
            $Matter->addFromQreq($matterParams);
        } else {
            // 選択
            $matterParams = $Matter->getByMatterNo($params['matter_no']);
        }

        // 見積依頼
        $requestParams = [];
        $requestParams['matter_no'] = $matterParams['matter_no'];
        $requestParams['department_id'] = $matterParams['department_id'];
        $requestParams['staff_id'] = $matterParams['staff_id'];
        $requestParams['quote_limit_date'] = $params['quote_limit_date'];
        $requestParams['spec_kbn'] =  $params['spec_kbn'];
        $requestParams['semi_fireproof_area_flg'] =  $params['semi_fireproof_area_flg'];
        $requestParams['builder_standard_flg'] =  $params['builder_standard_flg'];
        $requestParams['use_ref_quote_flg'] =  $params['use_ref_quote_flg'];
        $requestParams['ref_matter_no'] =  $params['ref_matter_no'];
        $requestParams['ref_quote_no'] =  $params['ref_quote_no'];
        $requestParams['quote_request_kbn'] = $this->formatJsonArr($params['quote_request']);
        $requestParams['quote_requested_kbn'] = $this->formatJsonArr($params['quote_requested']);

        if ($draftFlg === config('const.flg.on')) {
            // 一時保存
            $requestParams['status'] = config('const.quoteRequestStatus.val.editing'); 
        } else {
            // 本保存
            $requestParams['status'] = config('const.quoteRequestStatus.val.requested'); 
        }

        $quoteRequestId = $QuoteRequest->add($requestParams);
        
        // 見積依頼明細
        $qreqDetailParams = $this->formatDetail($params);
        $QuoteRequestDetail->addList($quoteRequestId, $qreqDetailParams);

        // 見積依頼処理待ち順番取得
        $waitCnt = $this->getWaitCount($requestParams);

        $rtn = array(
            'quote_request_id' => $quoteRequestId,
            'matter_no' => $matterParams['matter_no'],
            'matter_name' => $matterParams['matter_name'],
            'quote_limit_date' => $requestParams['quote_limit_date'],
            'wait_count' => $waitCnt
        );

        return $rtn;
    }

    /**
     * 更新処理
     *
     * @param array $params
     * @param int $draftFlg 一時保存フラグ
     * @return void
     */
    private function saveUpdate($params, $draftFlg) {
        $QuoteRequest = new QuoteRequest();
        $QuoteRequestDetail = new QuoteRequestDetail();

        // 見積依頼
        $requestParams = [];
        $requestParams['quote_request_id'] = $params['quote_request_id'];
        $requestParams['quote_limit_date'] = $params['quote_limit_date'];
        $requestParams['spec_kbn'] =  $params['spec_kbn'];
        $requestParams['semi_fireproof_area_flg'] =  $params['semi_fireproof_area_flg'];
        $requestParams['builder_standard_flg'] =  $params['builder_standard_flg'];
        $requestParams['use_ref_quote_flg'] =  $params['use_ref_quote_flg'];
        $requestParams['ref_matter_no'] =  $params['ref_matter_no'];
        $requestParams['ref_quote_no'] =  $params['ref_quote_no'];
        $requestParams['quote_request_kbn'] = $this->formatJsonArr($params['quote_request']);
        $requestParams['quote_requested_kbn'] = $this->formatJsonArr($params['quote_requested']);
        
        if ($draftFlg === config('const.flg.on')) {
            // 一時保存
            $requestParams['status'] = config('const.quoteRequestStatus.val.editing'); 
        } else {
            // 本保存
            $requestParams['status'] = config('const.quoteRequestStatus.val.requested'); 
        }

        $QuoteRequest->updateById($requestParams);
        
        // 見積依頼明細
        $qreqDetailParams = $this->formatDetail($params);
        $QuoteRequestDetail->updateList($params['quote_request_id'], $qreqDetailParams);

        // 見積依頼処理待ち順番取得
        $waitCnt = $this->getWaitCount($requestParams);

        $rtn = array(
            'quote_request_id' => $params['quote_request_id'],
            'matter_no' => $params['matter_no'],
            'matter_name' => $params['matter_name'],
            'quote_limit_date' => $requestParams['quote_limit_date'],
            'wait_count' => $waitCnt
        );

        return $rtn;
    }

    /**
     * 入力された見積明細データをDBに登録できる形に整形
     *
     * @param array $params
     * @return 
     */
    private function formatDetail($params) {
        $qreqDetailParams = [];
        $tmpKeyArr = [];
        foreach ($params as $key => $val) {
            if (strpos($key, self::SYMBOL_STR) === 0) {
                $tmpKeyArr[str_replace(self::SYMBOL_STR, '', $key)] = $val;
            }
        }
        $cnt = 0;
        $tmpKeyNameArr = [];
        foreach ($tmpKeyArr as $key => $val) {
            $tmpArr = explode(self::JOIN_STR, $key);
            if (count($tmpArr) >= self::VAL01_CNT) {
                // 見積依頼項目・仕様項目ヘッダID・仕様項目明細IDごとに1レコードにまとめる
                $keyName = $tmpArr[0].self::JOIN_STR.$tmpArr[1].self::JOIN_STR.$tmpArr[2];
                if (isset($tmpKeyNameArr[$keyName])) {
                    switch(count($tmpArr)) {
                        case self::VAL01_CNT:
                            $qreqDetailParams[$tmpKeyNameArr[$keyName]]['input_value01'] = $val;
                            break;
                        case self::VAL02_CNT:
                            $qreqDetailParams[$tmpKeyNameArr[$keyName]]['input_value02'] = $val;
                            break;
                    }
                } else {
                    $qreqDetailParams[$cnt]['quote_request_kbn'] = $tmpArr[0];
                    $qreqDetailParams[$cnt]['spec_item_header_id'] = $tmpArr[1];
                    $qreqDetailParams[$cnt]['spec_item_detail_id'] = $tmpArr[2];
                    switch(count($tmpArr)) {
                        case self::VAL01_CNT:
                            $qreqDetailParams[$cnt]['input_value01'] = $val;
                            break;
                        case self::VAL02_CNT:
                            $qreqDetailParams[$cnt]['input_value02'] = $val;
                            break;
                    }

                    $tmpKeyNameArr[$keyName] = $cnt;
                }
            }
            $cnt++;
        }

        return $qreqDetailParams;
    }

    /**
     * 入力フォームテンプレートデータリスト取得
     *
     * @param array $qreqDataList 見積依頼明細データリスト
     * @param array $quoteRequestKbn 見積依頼項目リスト
     * @return 入力フォームテンプレートのデータリスト
     */
    private function getTemplates($qreqDataList, $quoteRequestKbn) {
        $SpecItemHeader = new SpecItemHeader();
        $templates = null;

        // 見積依頼明細で使用している仕様項目ヘッダーIDを取得
        $headerIdList = [];
        foreach ($qreqDataList as $rec) {
            if (!isset($headerIdList[$rec['quote_request_kbn']])) {
                $headerIdList[$rec['quote_request_kbn']] = $rec['spec_item_header_id'];
            }
        }
        // 仕様項目データ取得
        $usedKbnList = [];
        foreach ($headerIdList as $kbn => $headerId) {
            $template = $SpecItemHeader->getQuoteRequestTemplateDataById($headerId);
            if (is_null($templates)) {
                $templates = $template;
            } else {
                $templates = $templates->concat($template);
            }
            $usedKbnList[] = $kbn;
        }
        // 見積依頼明細に存在しない見積依頼項目の仕様項目データ取得
        foreach ($quoteRequestKbn as $kbn) {
            if (!in_array($kbn, $usedKbnList)) {
                $template = $SpecItemHeader->getQuoteRequestTemplateDataByQuoteRequestKbn($kbn);
                if (is_null($templates)) {
                    $templates = $template;
                } else {
                    $templates = $templates->concat($template);
                }
            }
        }

        return $templates;
    }

    /**
     * 見積依頼項目のテンプレートデータ取得
     *
     * @param Request $request
     * @return void
     */
    public function getTemplateData(Request $request) 
    {
        $templates = [];
        $SpecItemHeader = new SpecItemHeader();
        $Choice = new Choice();

        // リクエストデータ取得
        $params = $request->request->all();
        $quoteRequestKbns = json_decode($params['quote_request_kbn'], true);

        $temp = [];
        foreach ($quoteRequestKbns as $key => $kbn) {
            $temp = $SpecItemHeader->getQuoteRequestTemplateDataByQuoteRequestKbn($kbn)->toArray();
            $templates = array_merge($templates, $temp);
        }
        $choiceList = $Choice->getAllData();

        $rtnResult = array(
            'templates' => $templates,
            'choiceList' => $choiceList,
        );

        return $rtnResult;
    }

    /**
     * 見積依頼処理待ち順番取得
     *
     * @param array $requestParams
     * @return 見積依頼処理待ち順番リスト
     */
    private function getWaitCount($requestParams) {
        $Construction = new Construction();
        $QuoteRequest = new QuoteRequest();
        $QuoteDetail = new QuoteDetail();

        // 見積依頼処理待ち順番取得
        $waitCount = [];
        // 見積依頼項目リスト取得
        $quoteReqList = $Construction->getQreqData(config('const.flg.on'))->keyBy('construction_id');
        // 画面で選択した見積依頼項目を配列化
        $targetQreqArr = $this->castArrayFromJsonArr(($requestParams['quote_request_kbn']));
        // 選択した見積依頼項目を含むリクエスト中の見積依頼データを取得
        $waitingQreqList = $QuoteRequest->getRequestingData($targetQreqArr);

        
        $waitCnt = [];
        $quoteNoList = [];
        foreach ($waitingQreqList as $rec) {
            // 取得した見積り依頼データの見積依頼項目を配列可
            $quoteRequestKbn = $this->castArrayFromJsonArr($rec->quote_request_kbn);

            // 見積依頼項目をそれぞれカウントする
            foreach ($quoteRequestKbn as $req) {
                // 関係ない見積依頼項目をスキップ
                if (!in_array($req, $targetQreqArr)) continue;

                if (isset($waitCnt[$req])) {
                    $waitCnt[$req]['cnt'] += 1;
                } else {
                    $waitCnt[$req] = array('cnt' => 1, 'construction_id' => $req, 'construction_name' => $quoteReqList[$req]->construction_name);
                }
            }
            
            if (!is_null($rec->quote_id)) {
                // 見積が存在する見積番号を配列に格納
                $quoteNoList[] = $rec->quote_no;
            }
        }

        if (count($quoteNoList) > 0) {
            // 対象の見積IDの工事区分別積算完了数を取得
            $completeCntList = $QuoteDetail->getCountComplete($quoteNoList);
            foreach ($completeCntList as $compItem) {
                // 積算完了分を減算
                if (isset($waitCnt[$compItem->construction_id])) {
                    $waitCnt[$compItem->construction_id]['cnt'] -= $compItem->cnt;
                }
            }
        }

        foreach ($waitCnt as $val) {
            $waitCount[] = $val;
        }

        return $waitCount;
    }

    /**
     * json格納用に整形
     *
     * @param string $param  (ex)1,2,4,5 カンマ区切り
     * @return string json型格納用の文字列
     */
    private function formatJsonArr($param) {
        return '['.$param.']';
    }

    /**
     * json型配列から配列にキャスト
     *
     * @param string $data  (ex)[2,4,5] json型に格納されているintの配列
     * @return array
     */
    private function castArrayFromJsonArr($data) {
        $rtnArray = array();
        $tmp = str_replace('[', '', $data);
        $tmp = str_replace(']', '', $tmp);
        if (strlen($tmp) > 0) {
            $tmpArr = explode(',', $tmp);
            foreach ($tmpArr as $item) {
                $rtnArray[] = (int)$item;
            }
        }
        return $rtnArray;
    }
    
    /**
     * 見積依頼項目計算
     *
     * @param string $param
     * @return void
     */
    private function calcQreqItems($param) {
        $rtnNum = 0;
        $tmpArr = $this->convArr($param);
        foreach ($tmpArr as $k) {
            $rtnNum += (int)$k;
        }
        return $rtnNum;
    }

    /**
     * カンマ区切りを配列化する
     *
     * @param string $param
     * @return void
     */
    private function convArr($param) {
        return explode(',', $param);
    }
    
    /**
     * バリデーションチェック（STEP1画面）
     * @param Request $request
     * @return type
     */
    private function isValidStep1($request)
    {
        $this->validate($request, [
            'customer_id' => 'required',
            'owner_name' => 'required',
            'quote_limit_date' => 'required',
            'architecture_type' => 'required',
            'spec_kbn' => 'required',
        ]);
    }

    /**
     * バリデーションチェック（ファイルアップロード用）
     *
     * @param Request $request
     * @param array $uploadFileList
     * @return boolean
     */
    private function isValidFile(Request $request, $uploadFileList)
    {
        $checkList = [];
        foreach ($uploadFileList as $fileNm => $fileObj) {
            $checkList = array_merge($checkList, array($fileNm => 'mimes_except:'.config('const.forbidden_extention')));
        }

        if (count($checkList) > 0) {
            return $this->validate($request, $checkList);
        } else {
            return true;
        }
    }

    /**
     * 見積提出期限チェック
     *
     * @param Request $request
     * @return boolean
     */
    private function isValidLimitDate(Request $request)
    {
        $today = Carbon::today()->format('Y/m/d');
        $this->validate($request, [
            'quote_limit_date' => 'after_or_equal:'.$today,
        ],
        [
            'quote_limit_date.after_or_equal' => '今日以降の日付を指定してください。',
        ]);
    }
}
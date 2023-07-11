<?php
namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use Session;
use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\Quote;
use App\Models\QuoteVersion;
use App\Models\QuoteDetail;
use App\Models\QuoteRequest;
// use App\Models\QuoteRequestDetail;
use App\Models\QuoteReportTemp;
use App\Models\Customer;
use App\Models\Person;
use App\Models\Construction;
use App\Models\Product;
// use App\Models\ProductPrice;
use App\Models\Supplier;
use App\Models\SupplierMaker;
use App\Models\SupplierFile;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Reserve;
use App\Models\General;
use App\Models\LockManage;
use App\Models\NumberManage;
use App\Models\System;
use App\Models\ApprovalHeader;
use App\Models\Notice;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Libs\SystemUtil;
// use App\Models\ProductCampaignPrice;
use App\Models\ProductCustomerPrice;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\ShipmentDetail;
use Illuminate\Support\Facades\Storage;
use \Symfony\Component\HttpFoundation\StreamedResponse;
use App\Providers\OrderServiceProvider;
use Illuminate\Support\Facades\Validator;

/**
 * 見積登録
 */
class QuoteEditController extends Controller
{
    const SCREEN_NAME = 'quote-edit';

    const PRINTED_MARK = ' *';

    private $quoteVerDefault = null;

    // サービス
    private $OrderServiceProvider;

    /**
     * コンストラクタ
     */
    public function __construct(OrderServiceProvider $OrderServiceProvider)
    {
        // ログインチェック
        $this->middleware('auth');
        $this->quoteVerDefault = [
            'quote_create_date' => Carbon::today()->format('Y/m/d'),
            'quote_enabled_limit_date' => '１か月',
            'payment_condition' => config('const.paycon.usual'),
        ];
        $this->OrderServiceProvider = $OrderServiceProvider;
        $this->OrderServiceProvider->initialize($this);
    }

    /**
     * 初期表示
     * @param Request $request
     * @param int $matterId 案件ID
     * @return type
     */
    public function index(Request $request, $matterId = null)
    {
        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $Matter = new Matter();
        $Quote = new Quote();
        $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();
        $QuoteRequest = new QuoteRequest();
        $Customer = new Customer();
        $Person = new Person();
        $Construction = new Construction();
        // $Product = new Product();
        // $ProductPrice   = new ProductPrice();
        // $ProductCampaignPrice = new ProductCampaignPrice();
        $Supplier = new Supplier();
        $SupplierMaker  = new SupplierMaker();
        $SupplierFile = new SupplierFile();
        $LockManage = new LockManage();
        $General = new General();
        $NumberManage = new NumberManage();
        $System = new System();
        $SystemUtil = new SystemUtil();

        // 画面へ渡す
        $lockData = null;
        $mainData = null;
        $quoteLayerList = null;
        $quoteDetailList = null;
        $customerList = null;
        $ownerList = null;
        $customerOwnerList = null;
        $architectureList = null;
        $personList = null;
        // $customerId = null;
        $requestedList = null;
        $qreqList = null;
        $priceList = null;
        $payConList = null;
        $allocList = null;
        $makerList = null;
        $supplierList = null;
        $supplierMakerList = null;
        $supplierFileList = null;
        $woodList = null;
        $gradeList = null;
        $initTree = [];
        $initConstructionBranch = config('const.quoteConstructionInfo');
        $taxRateLockFlg = config('const.flg.on');

        DB::beginTransaction();
        try {

            // 建築種別リスト取得
            $architectureList = $General->getCategoryList(config('const.general.arch'));
            // 相手先担当者リスト取得
            $personList = $Person->getComboList(config('const.person_type.customer'));
            // 見積依頼項目リスト取得
            $qreqList = $Construction->getQreqData(config('const.flg.on'));
            // 支払条件リスト取得
            $payConList = $General->getCategoryList(config('const.general.paycon'));
            // 価格区分リスト取得
            $priceList = $General->getCategoryList(config('const.general.price'));
            // 引当区分リスト取得
            $allocList = $General->getCategoryList(config('const.general.alloc'));
            // 税率取得
            $taxInfo = $System->getByPeriod();
            $taxRateLockFlg = $taxInfo->tax_rate_lock_flg;
        
            $quoteVerList = [];

            // TreeView初期状態
            $initTree = $QuoteDetail::getTopTreeData();
            // 追加部材用の工事区分データ取得
            $addLayer = $Construction->getAddFlgData();
            
            if (is_null($matterId)) {
                // 案件なし
                
                // コンボデータ取得
                // 得意先名リスト取得
                $customerList = $Customer->getComboList();
                // 施主名リスト取得
                $ownerList = $Matter->getOwnerList();
                // 顧客配下の施主名取得
                $customerOwnerList = $Matter->getCustomerOwnerList();
                
                // 仕入先メーカーリスト取得
                $tmpSupplierMakerList = $SupplierMaker->getComboListByMakerId();
                $supplierMakerList = $tmpSupplierMakerList->mapToGroups(function ($item, $key) {
                    return [$item['maker_id'] => $item];
                });
                // メーカーリスト取得
                $makerList = $Supplier->getBySupplierKbn(array(config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.maker')));
                // 仕入先リスト取得
                $supplierList = $Supplier->getBySupplierKbn(array(config('const.supplierMakerKbn.supplier')));
                // 木材
                $woodList = $General->getConvCadItem(config('const.general.wood'), config('const.general.woodconv'), true);
                // 等級
                $gradeList = $General->getConvCadItem(config('const.general.grade'), config('const.general.gradeconv'), true);
                // 仕入先ファイル
                $supplierFileList = $SupplierFile->getAll()->mapWithKeys(function ($item, $key) {
                    return [
                        $item->supplier_id => $item->file_name,
                    ];
                });
                // 見積
                $quoteList = $Quote->getComboList();
                // 案件リストを作成(見積が存在するもののみ)
                $matterList = $Matter->getComboList();
                $matterNoList = $quoteList->pluck('matter_no')->toArray();
                $matterList = $matterList->filter(function ($value, $key) use($matterNoList) {
                    return (in_array($value->matter_no, $matterNoList));
                });
                $matterList = $matterList->values();

                // 空データ作成
                $mainData = $Quote;
                $mainData->no_matter_flg = true;

                $QuoteVersion->quote_version = config('const.quoteCompVersion.number');
                $QuoteVersion->caption = config('const.quoteCompVersion.caption');
                $QuoteVersion->caption_tab = config('const.quoteCompVersion.caption');
                $QuoteVersion->quote_create_date = $this->quoteVerDefault['quote_create_date'];
                $QuoteVersion->quote_limit_date = null;
                $QuoteVersion->quote_enabled_limit_date = $this->quoteVerDefault['quote_enabled_limit_date'];
                $QuoteVersion->payment_condition = $this->quoteVerDefault['payment_condition'];
                $QuoteVersion->tax_rate = $taxInfo->tax_rate;
                $QuoteVersion->complete_flg_list = [];
                $quoteVerList[] = $QuoteVersion;

                Common::initModelProperty($mainData);
                $mainData->special_flg = config('const.flg.off');
                $mainData->version_list = $quoteVerList;

                // 追加部材の階層明細を作成
                $QuoteDetail->quote_version = config('const.quoteCompVersion.number');
                $QuoteDetail->construction_id = $addLayer['id'];
                $QuoteDetail->layer_flg = config('const.quoteConstructionInfo.layer_flg');
                $QuoteDetail->parent_quote_detail_id = config('const.quoteConstructionInfo.parent_quote_detail_id');
                $QuoteDetail->seq_no = config('const.quoteConstructionInfo.seq_no');
                $QuoteDetail->depth = config('const.quoteConstructionInfo.depth');
                $QuoteDetail->tree_path = config('const.quoteConstructionInfo.tree_path');
                $QuoteDetail->set_flg = config('const.quoteConstructionInfo.set_flg');
                $QuoteDetail->sales_use_flg = config('const.quoteConstructionInfo.sales_use_flg');
                $QuoteDetail->product_name = $addLayer['construction_name'];
                $QuoteDetail->quote_quantity = config('const.quoteConstructionInfo.quote_quantity');
                $QuoteDetail->min_quantity = config('const.quoteConstructionInfo.min_quantity');
                $QuoteDetail->order_lot_quantity = config('const.quoteConstructionInfo.order_lot_quantity');
                $QuoteDetail->set_kbn = config('const.quoteConstructionInfo.set_kbn');
                $QuoteDetail->row_print_flg = config('const.quoteConstructionInfo.row_print_flg');
                $QuoteDetail->price_print_flg = config('const.quoteConstructionInfo.price_print_flg');
                $QuoteDetail->add_flg = config('const.flg.on');
                $QuoteDetail->top_flg = config('const.quoteConstructionInfo.top_flg');
                $QuoteDetail->header = $addLayer['construction_name'];
                $QuoteDetail->to_layer_flg = config('const.quoteConstructionInfo.to_layer_flg');

                $addBranch = [];
                $zeroTree = [];
                $addBranch = $QuoteDetail;
                $addBranch['items'] = [];
                $zeroTree[] = $addBranch;
                $initTree[0]['items'] = $zeroTree;

                $quoteLayerList[] = $initTree;
                $quoteDetailList = collect([[$QuoteDetail]]);
                $requestedList = $QuoteRequest;
            } else {
                // 案件あり

                // コンボデータ取得
                // 得意先名リスト取得
                $customerList = $Customer;
                // 施主名リスト取得
                $ownerList = $Matter;
                // 顧客配下の施主名取得
                $customerOwnerList = $Matter;

                // メインデータ
                $matterId = (int)$matterId;
                // 見積データ取得
                $mainData = $Matter->getQuoteData($matterId);
                if (is_null($mainData)) {
                    // 案件データなし
                    throw new NotFoundHttpException();
                } else if (is_null($mainData['quote_id'])) {
                    // 見積データなし
                    $mainData->no_quote_flg = true;
                    $personList = $Person->getComboList(config('const.person_type.customer'), $mainData->customer_id);

                    // 見積依頼データの依頼区分取得
                    $requestedList = $QuoteRequest->getQuoteRequestByMatterNo($mainData['matter_no']);
                    foreach ($requestedList as $req) {
                        $req->quote_request_kbn_arr = $this->castArrayFromJsonArr($req['quote_request_kbn']); 
                    }

                    // 見積データ登録
                    $quoteParams = [];
                    $quoteParams['quote_no'] = $NumberManage->getSeqNo(config('const.number_manage.kbn.quote'), Carbon::today()->format('Ym'));
                    $quoteParams['matter_no'] = $mainData['matter_no'];
                    $quoteParams['special_flg'] = config('const.flg.off');
                    $quoteId = $Quote->add($quoteParams);

                    // 画面に渡す
                    $mainData->matter_id = $matterId;
                    $mainData->matter_no = $quoteParams['matter_no'];
                    $mainData->quote_id = $quoteId;
                    $mainData->quote_no = $quoteParams['quote_no'];
                    $mainData->special_flg = $quoteParams['special_flg'];

                    // 見積版データ作成
                    $QuoteVersion->quote_version = config('const.quoteCompVersion.number');
                    $QuoteVersion->caption = config('const.quoteCompVersion.caption');
                    $QuoteVersion->caption_tab = config('const.quoteCompVersion.caption');
                    $QuoteVersion->quote_create_date = $this->quoteVerDefault['quote_create_date'];
                    $QuoteVersion->quote_limit_date = null;
                    $QuoteVersion->quote_enabled_limit_date = $this->quoteVerDefault['quote_enabled_limit_date'];
                    $QuoteVersion->payment_condition = $this->quoteVerDefault['payment_condition'];
                    $QuoteVersion->tax_rate = $taxInfo->tax_rate;
                    $QuoteVersion->complete_flg_list = [];
                    // $quoteVerList[] = $QuoteVersion;
                    // $mainData->version_list = $quoteVerList;

                    // 0版データ登録
                    $quoteVerParams = [];
                    $quoteVerParams['quote_no'] = $quoteParams['quote_no'];
                    $quoteVerParams['quote_version'] = $QuoteVersion->quote_version;
                    $quoteVerParams['caption'] = $QuoteVersion->caption;
                    $quoteVerParams['department_id'] = $mainData['department_id'];
                    $quoteVerParams['staff_id'] = $mainData['staff_id'];
                    $quoteVerParams['payment_condition'] = $QuoteVersion->payment_condition;
                    $quoteVerParams['tax_rate'] = $QuoteVersion->tax_rate;
                    $quoteVerParams['quote_create_date'] = $QuoteVersion->quote_create_date;
                    $quoteVerParams['quote_enabled_limit_date'] = $QuoteVersion->quote_enabled_limit_date;
                    $quoteVersionId = $QuoteVersion->add($quoteVerParams);
                    $QuoteVersion->quote_version_id = $quoteVersionId;
                    $quoteVerList[] = $QuoteVersion;
                    $mainData->version_list = $quoteVerList;
                    
                    // 追加部材データを作成
                    $QuoteDetail->quote_no = $quoteParams['quote_no'];
                    $QuoteDetail->quote_version = config('const.quoteCompVersion.number');
                    $QuoteDetail->construction_id = $addLayer['id'];
                    $QuoteDetail->layer_flg = config('const.quoteConstructionInfo.layer_flg');
                    $QuoteDetail->parent_quote_detail_id = config('const.quoteConstructionInfo.parent_quote_detail_id');
                    $QuoteDetail->seq_no = config('const.quoteConstructionInfo.seq_no');
                    $QuoteDetail->depth = config('const.quoteConstructionInfo.depth');
                    $QuoteDetail->tree_path = config('const.quoteConstructionInfo.tree_path');
                    $QuoteDetail->set_flg = config('const.quoteConstructionInfo.set_flg');
                    $QuoteDetail->sales_use_flg = config('const.quoteConstructionInfo.sales_use_flg');
                    $QuoteDetail->product_name = $addLayer['construction_name'];
                    $QuoteDetail->quote_quantity = config('const.quoteConstructionInfo.quote_quantity');
                    $QuoteDetail->min_quantity = config('const.quoteConstructionInfo.min_quantity');
                    $QuoteDetail->order_lot_quantity = config('const.quoteConstructionInfo.order_lot_quantity');
                    $QuoteDetail->set_kbn = config('const.quoteConstructionInfo.set_kbn');
                    $QuoteDetail->row_print_flg = config('const.quoteConstructionInfo.row_print_flg');
                    $QuoteDetail->price_print_flg = config('const.quoteConstructionInfo.price_print_flg');
                    $QuoteDetail->add_flg = config('const.flg.on');
                    $QuoteDetail->top_flg = config('const.quoteConstructionInfo.top_flg');
                    $QuoteDetail->header = $addLayer['construction_name'];
                    $QuoteDetail->to_layer_flg = config('const.quoteConstructionInfo.to_layer_flg');
                    $QuoteDetail->over_quote_detail_id = 0;

                    // 追加部材データ登録
                    $quoteDetailParams = $QuoteDetail->toArray();
                    $QuoteDetail->addEx($quoteDetailParams);

                    // グリッド用に整形
                    $gridDataList = $SystemUtil->addFilterTreePathInfo(collect([$QuoteDetail]));
                    $quoteDetailList = collect($gridDataList);

                    // ツリー
                    $addBranch = [];
                    $zeroTree = [];
                    $addBranch = $QuoteDetail;
                    $addBranch['items'] = [];
                    $zeroTree[] = $addBranch;
                    $initTree[0]['items'] = $zeroTree;
                    $quoteLayerList[] = $initTree;
                    
                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                    $keys = array($quoteId, $matterId);

                    $lockCnt = 0;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // 案件あり＆見積なしで入ってきた場合は、必ずロック取得
                        $mode = config('const.mode.edit');
                        if (!$isLocked && $mode == config('const.mode.edit')) {
                            // 編集モードかつ、ロックされていない場合はロック取得
                            $lockDt = Carbon::now();
                            $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $keys[$i]);
                        }
                        // ロックデータ取得
                        $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                        if (!is_null($lockData) 
                            && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                            && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                                $lockCnt++;
                        }
                    }
                    if (count($tableNameList) === $lockCnt) {
                        $isOwnLock = config('const.flg.on');
                    } else {
                        DB::rollBack();
                    }
                } else {
                    // 見積データあり

                    $personList = $Person->getComboList(config('const.person_type.customer'), $mainData->customer_id);

                    // 見積依頼データの依頼区分取得
                    $requestedList = $QuoteRequest->getQuoteRequestByMatterNo($mainData['matter_no']);
                    foreach ($requestedList as $req) {
                        $req->quote_request_kbn_arr = $this->castArrayFromJsonArr($req['quote_request_kbn']); 
                    }

                    // 見積版データ取得
                    $quoteVerList = $QuoteVersion->getDataList($mainData['quote_no']);

                    $treeDataList = [];
                    $gridDataList = [];
                    foreach ($quoteVerList as $i => $versionRec) {

                        // 印刷判定
                        if (empty($versionRec['print_date'])) {
                            $quoteVerList[$i]->caption_tab = $versionRec['caption'];
                        } else {
                            $quoteVerList[$i]->caption_tab = $versionRec['caption'] . self::PRINTED_MARK;
                        }

                        // 添付ファイル取得
                        $fileNameList = [];
                        // フォルダ内のファイル全取得
                        $fileList = Storage::files(config('const.uploadPath.quote_version').$versionRec['quote_version_id']);
                        foreach ($fileList as $file) {
                            $filePath = Storage::url($file);
                            $tmps = explode('/', $filePath);
                            $fileName = $tmps[count($tmps) - 1];
                            $fileNameList[] = $fileName;
                        }
                        // 見積版データにファイル名リストをセット
                        $quoteVerList[$i]->file_list = $fileNameList;

                        // 見積階層データ取得　MEMO:getTreeDataの第3引数指定で階層データのみに絞ると、グリッドとfilter_tree_pathがずれるので指定しない
                        $treeDataList[$i] = $QuoteDetail->getTreeData($mainData['quote_no'], $versionRec['quote_version']);
                        $SystemUtil->deleteNotLayerRecord($treeDataList[$i][0]);

                        // 見積明細データ取得
                        $gridData = $QuoteDetail->getDataList($mainData['quote_no'], $versionRec['quote_version']);
                        $gridDataList[$i] = $SystemUtil->addFilterTreePathInfo($gridData);

                        // 積算完了された工事区分を用意（版単位）
                        $completeFlgList = [];
                        foreach ($gridDataList[$i] as $value) {
                            if ($value->complete_flg == config('const.flg.on')) {
                                $completeFlgList[] = $value->construction_id;
                            }
                        }
                        $quoteVerList[$i]->complete_flg_list = $completeFlgList;
                    }
                    $quoteLayerList = collect($treeDataList);
                    $quoteDetailList = collect($gridDataList);
                    
                    // メインデータに見積版データをセット
                    $mainData->version_list = $quoteVerList;

                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                    $keys = array($mainData['quote_id'], $mainData['matter_id']);

                    $lockCnt = 0;
                    $lockDt = null;
                    $lockUserName = null;
                    $partLockFlg = false;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // GETパラメータからモード取得
                        // $mode = config('const.mode.show');
                        $mode = $request->query(config('const.query.mode'));
                        if (!$isLocked && $mode == config('const.mode.edit')) {
                            // 編集モードかつ、ロックされていない場合はロック取得
                            $lockDt = Carbon::now();
                            $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $keys[$i]);
                        }
                        // ロックデータ取得
                        $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                        if (!is_null($lockData)) {
                            $partLockFlg = true; 
                            $lockDt = $lockData->lock_dt;
                            $lockUserName = $lockData->lock_user_name;
                            if ($lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                                && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                                $lockCnt++;
                            }
                        }
                    }
                    if (count($tableNameList) === $lockCnt) {
                        $isOwnLock = config('const.flg.on');
                    } else {
                        DB::rollBack();
                        if ($partLockFlg && is_null($lockData)) {
                            $lockData = $LockManage;
                            $lockData->id = -1;
                            $lockData->lock_dt = $lockDt;
                            $lockData->lock_user_name = $lockUserName;
                        }
                    }
                }

                if ($isOwnLock) {
                    // 編集モードの場合
                    // コンボデータ取得

                    // 仕入先メーカーリスト取得
                    $tmpSupplierMakerList = $SupplierMaker->getComboListByMakerId();
                    $supplierMakerList = $tmpSupplierMakerList->mapToGroups(function ($item, $key) {
                        return [$item['maker_id'] => $item];
                    });
                    // メーカーリスト取得
                    $makerList = $Supplier->getBySupplierKbn(array(config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.maker')));
                    // 仕入先リスト取得
                    $supplierList = $Supplier->getBySupplierKbn(array(config('const.supplierMakerKbn.supplier')));
                    // 木材
                    $woodList = $General->getConvCadItem(config('const.general.wood'), config('const.general.woodconv'), true);
                    // 等級
                    $gradeList = $General->getConvCadItem(config('const.general.grade'), config('const.general.gradeconv'), true);
                    // 仕入先ファイル
                    $supplierFileList = $SupplierFile->getAll()->mapWithKeys(function ($item, $key) {
                        return [
                            $item->supplier_id => $item->file_name,
                        ];
                    });
                    // 見積
                    $quoteList = $Quote->getComboList();
                    // 案件リストを作成(見積が存在するもののみ)
                    $matterList = $Matter->getComboList();
                    $matterNoList = $quoteList->pluck('matter_no')->toArray();
                    $matterList = $matterList->filter(function ($value, $key) use($matterNoList) {
                        return (in_array($value->matter_no, $matterNoList));
                    });
                    $matterList = $matterList->values();
                } else {
                    //
                    // コンボデータ

                    // 仕入先メーカーリスト取得
                    $supplierMakerList = $SupplierMaker;
                    // メーカーリスト取得
                    $makerList = $Supplier;
                    // 仕入先リスト取得
                    $supplierList = $Supplier;
                    // 木材
                    $woodList = $General;
                    // 等級
                    $gradeList = $General;
                    // 仕入先ファイル
                    $supplierFileList = $SupplierFile;
                    // 見積
                    $quoteList = $Quote;
                    // 案件リストを作成(見積が存在するもののみ)
                    $matterList = $Matter;
                }
            }

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
            
            DB::commit();
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Quote.quote-edit') 
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('mainData', $mainData)
                ->with('quoteLayerList', json_encode($quoteLayerList))
                ->with('quoteDetailList', $quoteDetailList)
                ->with('customerList', $customerList)
                ->with('ownerList', $ownerList)
                ->with('customerOwnerList', $customerOwnerList)
                ->with('architectureList', $architectureList)
                ->with('personList', $personList)
                ->with('requestedList', $requestedList)
                ->with('qreqList', $qreqList)
                ->with('priceList', $priceList)
                ->with('payConList', $payConList)
                ->with('allocList', $allocList)
                ->with('makerList', $makerList)
                ->with('supplierList', $supplierList)
                ->with('supplierMakerList', $supplierMakerList)
                ->with('supplierFileList', $supplierFileList)
                ->with('woodList', $woodList)
                ->with('gradeList', $gradeList)
                ->with('matterList', $matterList)
                ->with('quoteList', $quoteList)
                ->with('initTree', json_encode($initTree))
                ->with('initConstructionBranch', json_encode($initConstructionBranch))
                ->with('taxRateLockFlg', $taxRateLockFlg)
                ->with('addLayerId', $addLayer['id'])
                ->with('quoteVersionDefault', json_encode($this->quoteVerDefault))
                ;
    }
    
    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result = array('status' => false, 'msg' => '', 'id' => 0, 'matter_no' => 0);
        
        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        $LockManage = new LockManage();
        $Matter = new Matter();
        
        $newFlg = false;
        if (empty($params['quote_id'])) {
            $newFlg = true;
        }

        DB::beginTransaction();
        try {
            // 登録
            $matterId = 0;        // outパラメータ
            $quoteId = 0;         // outパラメータ
            $this->saveQuote($newFlg, $params, $matterId, $quoteId);

            $matter = $Matter->getById($matterId);

            // ロック中確認
            $isOwnLock = false;
            if ($newFlg) {
                $isOwnLock = true;
            } else {
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($quoteId , $matterId);

                $lockCnt = 0;
                foreach ($tableNameList as $i => $tableName) {
                    // ロックデータ取得
                    $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                    if (!is_null($lockData) 
                        && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                        && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                            $lockCnt++;
                    }
                }

                $isOwnLock = count($tableNameList) === $lockCnt;
            }
            
            if ($isOwnLock) {
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);

                DB::commit();
                $result['id'] = $matterId;
                $result['matter_no'] = $matter->matter_no;
                $result['status'] = true;
                $result['msg'] = config('message.success.save');
                
                Session::flash('flash_success', config('message.success.save'));
            } else {
                DB::rollBack();
                $result['status'] = false;
                $result['msg'] = config('message.error.getlock');
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getFirstMessage();
            }
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }
        return \Response::json($result);
    }
    
    /**
     * 保存処理
     *
     * @param bool $newFlg 新規フラグ true:新規 false:更新
     * @param array $params （out）
     * @param int $matterId 案件ID（out）
     * @param int $quoteId 見積ID（out）
     * @return void
     */
    public function saveQuote($newFlg, &$params, &$matterId, &$quoteId){
        $SystemUtil = new SystemUtil();
        $Matter = new Matter();
        $Quote = new Quote();
        $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();
        $NumberManage = new NumberManage();
        $Customer = new Customer();

        // 得意先データ取得
        $customerData = $Customer->getChargeData((int)$params['customer_id']);
        $chargeDepartmentId = $customerData['charge_department_id'];
        $chargeStaffId = $customerData['charge_staff_id'];

        $quoteParams = [
            'id' => $params['quote_id'],
            'special_flg' => $params['special_flg'],
            'person_id' => $params['person_id'],
            'construction_period' => $params['construction_period'],
            'construction_outline' => $params['construction_outline'],
            'quote_report_to' => $params['quote_report_to'],
        ];
        if ($newFlg) {
            // 案件登録
            $matterParams = [];
            $matterParams['matter_no'] = $NumberManage->getSeqNo(config('const.number_manage.kbn.matter'), Carbon::today()->format('Ym'));
            $matterParams['matter_name'] = $params['matter_name'];
            $matterParams['matter_create_date'] = Carbon::today()->format('Y/m/d');
            $matterParams['customer_id'] = $params['customer_id'];
            $matterParams['owner_name'] = $params['owner_name'];
            $matterParams['architecture_type'] = $params['architecture_type'];
            $matterParams['department_id'] = $chargeDepartmentId;
            $matterParams['staff_id'] = $chargeStaffId;
            $matterId = $Matter->addFromQreq($matterParams);

            // 見積登録
            $quoteParams['quote_no'] = $NumberManage->getSeqNo(config('const.number_manage.kbn.quote'), Carbon::today()->format('Ym'));
            $quoteParams['matter_no'] = $matterParams['matter_no'];
            $quoteId = $Quote->add($quoteParams);
        } else {
            $matterId = (int)$params['matter_id'];
            $quoteId = (int)$params['quote_id'];

            // ロック確認　MEMO:開始前に一度チェックして無駄なDBアクセスを減らす
            $this->OrderServiceProvider->isOwnLocked($quoteId, $matterId);

            $matterData = $Matter->getById($matterId);
            $chargeDepartmentId = $matterData->department_id;
            $chargeStaffId = $matterData->staff_id;

            // 見積データ更新
            $Quote->updateById($quoteParams);
        }

        $quoteData = $Quote->getById($quoteId);         
        $diffParams = array_diff_key($params, $params['version_list'][config('const.quoteCompVersion.number')]);  // paramsにのみ存在する項目を抽出
        unset($diffParams['version_list']);
        foreach ($params['version_list'] as $key => $verData) {
            $verData = array_merge($verData, $diffParams);
            $quoteVersionId = $verData['quote_version_id'];
            $verData['department_id'] = $chargeDepartmentId;
            $verData['staff_id'] = $chargeStaffId;
            if ($verData['quote_version'] == config('const.quoteCompVersion.number')) {
                // 見積データ有 && 0版
                if ($newFlg) {
                    $quoteVersionId = $this->saveQuoteVerZero($verData, $quoteData, $quoteVersionId, true);
                }else{
                    // 変更前の見積版を取得
                    $beforeQuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
                    // 変更前の納品を取得
                    $deliveryDataList = $QuoteDetail->getDeliveryDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
                    // 変更前の売上明細を取得
                    $salesDetailDataList    = $QuoteDetail->getSalesDetailDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
                    $quoteVersionId = $this->saveQuoteVerZero($verData, $quoteData, $quoteVersionId, false);

                    // 見積の粗利率が基準値を下回った場合の処理
                    $SystemUtil->quoteFallBelowGrossProfitRateProcessing($params['quote_no'], $beforeQuoteVersionData);

                    // 変更後の見積明細       
                    $afterQuoteDetailData = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));

                    // 納品データのチェック
                    $this->OrderServiceProvider->isValidQuoteChangeState($deliveryDataList, $afterQuoteDetailData, true);
                    // 売上明細データのチェック
                    $this->OrderServiceProvider->isValidQuoteChangeState($salesDetailDataList, $afterQuoteDetailData, false);
                }
            }else{
                // 他版
                $quoteVersionId = $this->saveQuoteVer($verData, $quoteData, $quoteVersionId);
            }
            $params['version_list'][$key]['is_exists'] = ($params['version_list'][$key]['quote_version_id']  == 0)? false:true;
            $params['version_list'][$key]['quote_version_id'] = $quoteVersionId;
        }
    }


    /**
     * 積算完了
     *
     * @param Request $request
     * @return void
     */
    public function completeEstimation(Request $request)
    {
        $result = array('status' => false, 'msg' => '');
        
        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        DB::beginTransaction();
        try {
            $QuoteDetail = new QuoteDetail();
            $LockManage = new LockManage();

            do {
                // 保存処理
                $matterId = 0;        // outパラメータ
                $quoteId = 0;         // outパラメータ
                $this->saveQuote(false, $params, $matterId, $quoteId);

                // 積算完了の工事区分IDを取得
                $targetIdList = json_decode($params['estimation_ids']);

                // 更新
                $QuoteDetail->updateCompEstimation($params['quote_no'], $params['quote_version'], $params['is_estimation_release'], $targetIdList);

                // ロック中確認
                // 画面名から対象テーブルを取得
                $quoteId = (int)$params['quote_id'];
                $matterId = (int)$params['matter_id'];
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($quoteId , $matterId);

                $lockCnt = 0;
                foreach ($tableNameList as $i => $tableName) {
                    // ロックデータ取得
                    $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                    if (!is_null($lockData) 
                        && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                        && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                            $lockCnt++;
                    }
                }
                if (count($tableNameList) !== $lockCnt) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.getlock');
                    break;
                }

                $result['status'] = true;
            } while(false);

            if ($result['status']) {
                // 成功
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);
                DB::commit();
                Session::flash('flash_success', config('message.success.save'));
            } else {
                // 失敗
                if ($result['msg'] === '') {
                    $result['msg'] = config('message.error.save');
                }
                Session::flash('flash_error', $result['msg']);
                DB::rollBack();
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getFirstMessage();
            }
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }
        return \Response::json($result);
    }
    
    /**
     * 承認申請
     *
     * @param Request $request
     * @return void
     */
    public function apply(Request $request)
    {
        $result = array('status' => false, 'msg' => '');

        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        DB::beginTransaction();
        try {
            $SystemUtil = new SystemUtil();
            $QuoteVersion = new QuoteVersion();
            $LockManage = new LockManage();
            $ApprovalHeader = new ApprovalHeader();
            $Notice = new Notice();
            
            do {
                // 登録
                $matterId = 0;        // outパラメータ
                $quoteId = 0;         // outパラメータ
                $this->saveQuote(false, $params, $matterId, $quoteId);

                // 見積版取得
                $QuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], $params['quote_version']);

                // 承認申請
                $SystemUtil->applyForQuoteProcessing($QuoteVersionData);

                // 承認ステータス取得
                $approvalInfo = $ApprovalHeader->getByProcessId(config('const.approvalProcessKbn.quote'), $QuoteVersionData['id'], config('const.flg.off'));
                $quoteVersionStatus = config('const.quoteVersionStatus.val.applying');
                foreach ($approvalInfo as $rec) {
                    if($rec->status === config('const.approvalHeaderStatus.val.approved')) {
                        $quoteVersionStatus = config('const.quoteVersionStatus.val.approved');
                        // お知らせ追加
                        $noticeParam = [
                            'notice_flg' => config('const.flg.off'),
                            'staff_id' => $QuoteVersionData->staff_id,
                            'content' => config('message.notice.quote_approval'). '('. config('const.autoApprovalComment'). ')',
                            'redirect_url' => url('quote-list/' . '?quote_no=' . $QuoteVersionData->quote_no. '&'. 'fil_chk_not_approved_only_id=0')
                        ];
                        $Notice->add($noticeParam);
                    }
                    break;
                }

                // 見積版 見積状況を更新
                $updateResult = $QuoteVersion->updateStatusById($QuoteVersionData['id'], $quoteVersionStatus);
                if (!$updateResult) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.apply');
                    break;
                }

                // ロック解放
                $keys = array($params['quote_id'], $params['matter_id']);
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
                if (!$delLock) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.getlock');
                    break;
                }

                $result['status'] = true;
            } while(false);

            if ($result['status']) {
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);
                // 成功
                Session::flash('flash_success', config('message.success.apply'));
                DB::commit();
            } else {
                // 失敗
                DB::rollBack();
                if ($result['msg'] === '') {
                    $result['msg'] = config('message.error.apply');
                }
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getFirstMessage();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }

        return \Response::json($result);
    }
    
    /**
     * 承認申請取消
     *
     * @param Request $request
     * @return void
     */
    public function applyCancel(Request $request)
    {
        $result = array('status' => false, 'msg' => '');

        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        DB::beginTransaction();
        try {
            $SystemUtil = new SystemUtil();
            $QuoteVersion = new QuoteVersion();
            $LockManage = new LockManage();

            do {
                // 登録
                $matterId = 0;        // outパラメータ
                $quoteId = 0;         // outパラメータ
                $this->saveQuote(false, $params, $matterId, $quoteId);

                // 見積版取得
                $QuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], $params['quote_version']);

                // 承認申請取消
                $cancelResult = $SystemUtil->cancelForProcessing(config('const.approvalProcessKbn.quote'), $QuoteVersionData['id']);
                if (!$cancelResult) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.cancel');
                    break;
                }

                // 見積版 見積状況を更新
                $updateResult = $QuoteVersion->updateStatusById($QuoteVersionData['id'], config('const.quoteVersionStatus.val.editing'));
                if (!$updateResult) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.cancel');
                    break;
                }

                // ロック中確認
                // 画面名から対象テーブルを取得
                $keys = array((int)$params['quote_id'], (int)$params['matter_id']);
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $lockCnt = 0;
                foreach ($tableNameList as $i => $tableName) {
                    // ロックデータ取得
                    $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                    if (!is_null($lockData) 
                        && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                        && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                            $lockCnt++;
                    }
                }
                if (count($tableNameList) !== $lockCnt) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.getlock');
                    break;
                }
                
                $result['status'] = true;
            } while(false);

            if ($result['status']) {
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);
                // 成功
                Session::flash('flash_success', config('message.success.apply'));
                DB::commit();
            } else {
                // 失敗
                DB::rollBack();
                if ($result['msg'] === '') {
                    $result['msg'] = config('message.error.apply');
                }
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getFirstMessage();
            }
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }
        
        return \Response::json($result);
    }

    /**
     * 受注確定
     *
     * @param Request $request
     * @return void
     */
    public function receivedOrder(Request $request)
    {
        $result = array('status' => false, 'msg' => '');
        
        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        DB::beginTransaction();
        try {
            $QuoteVersion = new QuoteVersion();
            $QuoteDetail = new QuoteDetail();
            $Customer = new Customer();
            $SystemUtil = new SystemUtil();
            $LockManage = new LockManage();

            // 得意先マスタをチェック
            if (!$Customer->isValidReceivedOrder($params['customer_id'])) {
                throw new ValidationException(config('message.error.customer.received_order_not_input'));
            }

            // 保存処理
            $matterId = 0;        // outパラメータ
            $quoteId = 0;         // outパラメータ
            $this->saveQuote(false, $params, $matterId, $quoteId);

            // 受注確定前の見積版を取得
            $beforeQuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));

            // 受注確定した見積明細ID
            $quoteDetailIdList = json_decode($params['quote_detail_ids']);

            // 受注確定を行っている版の明細を全取得
            $detailList = $QuoteDetail->getDataList($params['quote_no'], $params['quote_version']);

            // 受注確定する対象の明細のみを取得
            $receivedDetailList = $detailList->whereIn('id', $quoteDetailIdList);

            // 受注確定する明細の階層も含め、IDを配列に格納（階層がない場合は先にコピー登録しておく必要があるため）
            $relationIdArr = [];
            foreach ($receivedDetailList as $detail) {
                // tree_pathから関連レコードを特定
                $pathIds = explode(config('const.treePathSeparator'), $detail['tree_path']);

                foreach ($pathIds as $id) {
                    $relationIdArr[] = $id;
                }
                $relationIdArr[] = $detail['id'];
            }
            // 受注確定する関連データを格納
            $receivedRelDetailList = $detailList->whereIn('id', $relationIdArr);
            
            // // 受注確定を行っている版の全明細を配列化
            // $detailListArr = $detailList->keyBy('id');
            // $detailListArr = $detailListArr->toArray();

            // 受注確定フラグ更新
            $updateCnt = $QuoteDetail->updateReceivedOrderFlg($quoteDetailIdList);
            // TODO:受注確定フラグが立っている物を受注確定するパターンがありうるのか？（階層行など）
            // if ($updateCnt != count($quoteDetailIdList)) {
            //     throw new \Exception(config('message.error.save'));
            // }

            // 0版の見積明細データをすべて取得
            $zeroDetailList = $QuoteDetail->getDataList($params['quote_no'], config('const.quoteCompVersion.number'));
            // $zeroDetailList = $zeroDetailList->keyBy('id');
            // $zeroDetailListArr = $zeroDetailList->toArray();

            $layerLinkList = [];
            // 0版として見積明細をコピーINSERT
            foreach ($receivedRelDetailList as $detail) {

                // 明細or階層？
                if ($detail['layer_flg'] === config('const.flg.on')) {
                    // 階層

                    // 0版データの階層に、工事区分・深さ・階層名が一致するレコードが存在するか確認
                    $layerDataList = $zeroDetailList
                                ->where('layer_flg', config('const.flg.on'))
                                ->where('construction_id', $detail['construction_id'])
                                ->where('depth', $detail['depth'])
                                ->where('product_name', $detail['product_name'])
                                ;
                    // 工事区分階層の場合は階層名を見ない
                    if ($detail['depth'] == config('const.quoteConstructionInfo.depth')) {
                        $layerDataList = $zeroDetailList
                            ->where('construction_id', $detail['construction_id'])
                            ->where('depth', $detail['depth'])
                            ;
                    }

                    if (count($layerDataList) === 0) {
                        // 存在しない場合、INSERT

                        // コピーしない項目のみ値セット
                        $insRec = $detail->toArray();
                        $insRec['id'] = null;
                        $insRec['quote_version'] = config('const.quoteCompVersion.number');
                        $insRec['copy_quote_detail_id'] = $detail['id'];
                        $insRec['created_at'] = null;
                        $insRec['created_user'] = null;
                        $insRec['update_at'] = null;
                        $insRec['update_user'] = null;
                        if (isset($layerLinkList[$detail['parent_quote_detail_id']])) {
                            // 工事区分より下の階層
                            $parentRec = $layerLinkList[$detail['parent_quote_detail_id']];
                            $insRec['parent_quote_detail_id'] = $parentRec['id'];
                            $seqNo = $zeroDetailList
                                    ->where('parent_quote_detail_id', $insRec['parent_quote_detail_id'])
                                    ->max('seq_no')
                                    ;
                            if (is_null($seqNo)) {
                                $seqNo = 0;
                            }
                            $insRec['seq_no'] = $seqNo + 1;
                            if ($parentRec['tree_path'] == 0) {
                                $insRec['tree_path'] = $parentRec['id'];
                            } else {
                                $insRec['tree_path'] = $parentRec['tree_path'].config('const.treePathSeparator').$parentRec['id'];
                            }
                        } else {
                            // 工事区分階層
                            $insRec['parent_quote_detail_id'] = config('const.quoteConstructionInfo.parent_quote_detail_id');
                            $seqNo = $zeroDetailList
                                    ->where('parent_quote_detail_id', $insRec['parent_quote_detail_id'])
                                    ->max('seq_no')
                                    ;
                            if (is_null($seqNo)) {
                                $seqNo = 0;
                            }
                            $insRec['seq_no'] = $seqNo + 1;
                            $insRec['tree_path'] = config('const.quoteConstructionInfo.tree_path');
                        }

                        // 登録
                        $quoteDetailId = $QuoteDetail->addEx($insRec);

                        $insRec['id'] = $quoteDetailId;
                        // 受注確定元の版の明細IDと0版階層明細の紐付け
                        $layerLinkList[$detail['id']] = $insRec;
                        // 0版明細リストに追加する
                        $zeroDetailList[$quoteDetailId] = $insRec;
                    } else {
                        // 存在する場合、UPDATE（受注確定のロックをかけるのみ？販売額利用フラグ＆販売額も変わるかも）

                        // 受注確定元の版の明細IDと0版階層明細の紐付け
                        $layerData = null;
                        foreach ($layerDataList as $val) {
                            $layerData = $val;
                            break;  // 1件のはずだが念の為
                        }
                        $layerLinkList[$detail['id']] = $layerData;

                        if (in_array($detail['id'], $quoteDetailIdList)) {
                            // 受注確定対象の階層データの場合、更新
                            $layerData['sales_use_flg'] = $detail['sales_use_flg'];
                            $layerData['copy_quote_detail_id'] = $detail['id'];
                            $updateResult = $QuoteDetail->updateById($layerData['id'], $layerData);
                        }
                    }

                } else {
                    // 明細
                    
                    $insRec = $detail->toArray();
                    // 親階層
                    $parentRec = $layerLinkList[$detail['parent_quote_detail_id']];
                    
                    // コピーしない項目のみ値セット
                    $insRec['id'] = null;
                    $insRec['created_at'] = null;
                    $insRec['created_user'] = null;
                    $insRec['update_at'] = null;
                    $insRec['update_user'] = null;

                    $insRec['quote_version'] = config('const.quoteCompVersion.number');
                    $insRec['copy_quote_detail_id'] = $detail['id'];
                    $insRec['parent_quote_detail_id'] = $parentRec['id'];
                    $seqNo = $zeroDetailList
                            ->where('parent_quote_detail_id', $insRec['parent_quote_detail_id'])
                            ->max('seq_no')
                            ;
                    if (is_null($seqNo)) {
                        $seqNo = 0;
                    }
                    $insRec['seq_no'] = $seqNo + 1;
                    if ($parentRec['tree_path'] == 0) {
                        $insRec['tree_path'] = $parentRec['id'];
                    } else {
                        $insRec['tree_path'] = $parentRec['tree_path'].config('const.treePathSeparator').$parentRec['id'];
                    }
                    $insRec['received_order_flg'] = config('const.flg.off');

                    // 登録
                    $quoteDetailId = $QuoteDetail->addEx($insRec, $params['customer_id']);

                    $insRec['id'] = $quoteDetailId;
                    // // 受注確定元の版の明細IDと0版階層明細の紐付け
                    // $layerLinkList[$detail['id']] = $insRec;
                    // 0版明細リストに追加する
                    $zeroDetailList[$quoteDetailId] = $insRec;
                }
            }

            // 0版の階層明細の金額を更新
            $targetList = $SystemUtil->calcQuoteLayersTotal($params['quote_no'], config('const.quoteCompVersion.number'));
            foreach ($targetList as $id => $item) {
                if ($id === 0) continue;
                
                $updateResult = $QuoteDetail->updateTotalKng($id, $item);
            }

            // 見積0版金額更新
            $updateResult = $QuoteVersion->updateTotalKng($params['quote_no'], config('const.quoteCompVersion.number'));

            // 見積の粗利率が基準値を下回った場合の処理
            $SystemUtil->quoteFallBelowGrossProfitRateProcessing($params['quote_no'], $beforeQuoteVersionData);

            // ロック中確認
            // 画面名から対象テーブルを取得
            $quoteId = (int)$params['quote_id'];
            $matterId = (int)$params['matter_id'];
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            $keys = array($quoteId , $matterId);

            $lockCnt = 0;
            foreach ($tableNameList as $i => $tableName) {
                // ロックデータ取得
                $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                if (!is_null($lockData) 
                    && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                    && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                        $lockCnt++;
                }
            }
            
            if (count($tableNameList) === $lockCnt) {
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);

                DB::commit();
                $result['status'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getErrorMessage();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * 受注確定　取消
     *
     * @param Request $request
     * @return void
     */
    public function cancelReceivedOrder(Request $request) {
        $result = array('status' => false, 'msg' => '');
        
        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        DB::beginTransaction();
        try {
            $QuoteVersion = new QuoteVersion();
            $QuoteDetail = new QuoteDetail();
            $LockManage = new LockManage();
            $OrderDetail = new OrderDetail();
            $Reserve = new Reserve();
            $ProductCustomerPrice = new ProductCustomerPrice();
            $Sales = new Sales();
            $SalesDetail = new SalesDetail();
            $SystemUtil = new SystemUtil();

            // 保存処理
            $matterId = 0;        // outパラメータ
            $quoteId = 0;         // outパラメータ
            $this->saveQuote(false, $params, $matterId, $quoteId);

            // 受注確定取消前の見積版を取得
            $beforeQuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));

            // 受注確定を取り消す見積明細ID
            $quoteDetailIdList = json_decode($params['quote_detail_ids']);

            do {

                // 売上明細データ存在チェック
                $salesDetailList = $SalesDetail->getByQuoteDetailIdList($quoteDetailIdList);
                if (count($salesDetailList) > 0) {
                    // 売上明細データがある場合、削除させない
                    $result['status'] = false;
                    $result['msg'] = config('message.error.quote.delete_sales_detail');
                    break;
                }
                // 売上データ存在チェック
                $existSales = $Sales->hasSalesByParentQuoteDetailIdList($matterId, $quoteDetailIdList);
                if ($existSales) {
                    // 売上データがある場合、削除させない
                    $result['status'] = false;
                    $result['msg'] = config('message.error.quote.delete_sales');
                    break;
                }


                // 発注明細存在チェック
                $orderDetailList = $OrderDetail->getDataByQuoteDetailIdList($quoteDetailIdList);
                $tmpOrderDetailList = $orderDetailList;

                // 一時保存データを分離(=発注番号がブランク or NULL)は無視
                $orderDetailList = $orderDetailList->where('order_no', '<>', null)->where('order_no', '<>', '');
                $tmpOrderDetailList = $tmpOrderDetailList->whereIn('order_no', [null, '']);

                if (count($orderDetailList) > 0) {
                    // 発注データがある場合、削除させない
                    $result['status'] = false;
                    $result['msg'] = config('message.error.quote.delete_order');
                    break;
                }
                
                // 得意先別商品単価データ削除
                foreach ($quoteDetailIdList as $qDetailId) {
                    $ProductCustomerPrice->deleteByWhere([['quote_detail_id', $qDetailId]]);
                }

                // 発注一時保存データ削除
                if (count($tmpOrderDetailList) > 0) {
                    $OrderDetail->physicalDeleteListByIds($tmpOrderDetailList->pluck('id')->toArray());
                }

                // 引当存在チェック
                $reserveList = $Reserve->getByQuoteDetailIdList($quoteDetailIdList);
                if (count($reserveList) > 0) {
                    // 引当データがある場合、削除させない
                    $result['status'] = false;
                    $result['msg'] = config('message.error.quote.delete_reserve');
                    break;
                }

                // 対象の見積明細を取得
                $detailList = $QuoteDetail->getByIds($quoteDetailIdList);

                // 受注確定元明細データのIDリスト(※0版から参照した時の)
                $receivedIdList = $detailList
                                    ->where('copy_quote_detail_id', '!=', 0)
                                    ->pluck('copy_quote_detail_id')->unique()->toArray();
                
                // 受注確定元明細のコピー明細IDを取得
                $copyReceivedIdList = [];
                if (count($receivedIdList) > 0) {
                    $copyReceivedData = $QuoteDetail->getByCopyIds($receivedIdList, $params['quote_no']);
                    $copyReceivedIdList = $copyReceivedData->pluck('id')->unique()->toArray();
                }

                // 複製版にも受注確定フラグがたっているのでマージ
                $receivedIdList = array_merge($receivedIdList, $copyReceivedIdList);

                if (count($receivedIdList) > 0) {
                    // 受注確定元明細および受注確定元明細のコピー明細の、受注確定フラグを落とす
                    $QuoteDetail->updateReceivedOrderFlg($receivedIdList, true);
                }
                if (count($copyReceivedIdList) > 0) {
                    // コピー元受注確定フラグを落とす
                    $QuoteDetail->updateCopyReceivedOrderFlg($copyReceivedIdList, true);
                }

                // 対象の見積明細削除
                $QuoteDetail->deleteList($quoteDetailIdList);

                // 削除した明細があった階層の他の明細の連番振りなおし
                $parentQuoteDetailIdList = $detailList
                                    ->where('parent_quote_detail_id', '!=', 0)
                                    ->pluck('parent_quote_detail_id')
                                    ->unique()
                                    ->toArray();
                foreach ($parentQuoteDetailIdList as $parentQuoteDetailId) {
                    $childPartsList = $QuoteDetail->getChildQuoteDetailData($params['quote_no'], $parentQuoteDetailId)->toArray();
                    $seqNo = 0;
                    foreach($childPartsList as $row){
                        $row['seq_no'] = ++$seqNo;
                        $QuoteDetail->updateByIdEx($row['id'], $row);
                    }
                }

                // 0版の階層明細の金額を更新
                $targetList = $SystemUtil->calcQuoteLayersTotal($params['quote_no'], config('const.quoteCompVersion.number'));
                foreach ($targetList as $id => $item) {
                    if ($id === 0) continue;
                    
                    $updateResult = $QuoteDetail->updateTotalKng($id, $item);
                }

                // 見積0版金額更新
                $updateResult = $QuoteVersion->updateTotalKng($params['quote_no'], config('const.quoteCompVersion.number'));

                // 見積の粗利率が基準値を下回った場合の処理
                $SystemUtil->quoteFallBelowGrossProfitRateProcessing($params['quote_no'], $beforeQuoteVersionData);

                // ロック中確認
                // 画面名から対象テーブルを取得
                $quoteId = (int)$params['quote_id'];
                $matterId = (int)$params['matter_id'];
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($quoteId , $matterId);

                $lockCnt = 0;
                foreach ($tableNameList as $i => $tableName) {
                    // ロックデータ取得
                    $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                    if (!is_null($lockData) 
                        && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                        && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                            $lockCnt++;
                    }
                }
                
                if (count($tableNameList) !== $lockCnt) {
                    $result['status'] = false;
                    $result['msg'] = config('message.error.getlock');
                    break;
                }

                $result['status'] = true;
            } while (false);
            
            if ($result['status']) {
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);

                DB::commit();
                $result['msg'] = config('message.success.save');
                Session::flash('flash_success', config('message.success.cancel'));
            } else {
                DB::rollBack();
                if ($result['msg'] === '') {
                    $result['msg'] = config('message.error.save');
                }
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getFirstMessage();
            }
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }
        return \Response::json($result);
    }

    /**
     * 見積削除
     * @param Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = false;
        $resultMsg = '';

        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            $Quote = new Quote();
            $QuoteVersion = new QuoteVersion();
            $QuoteDetail = new QuoteDetail();
            $Order = new Order();
            $Reserve = new Reserve();
            $SalesDetail = new SalesDetail();

            do {
                // 発注データ存在確認
                $orderData = $Order->getByMatterQuote($params['matter_no'], $params['quote_no']);
                if (count($orderData) > 0) {
                    // 発注データがある場合、削除させない
                    $resultSts = false;
                    $resultMsg = config('message.error.quote.delete_order');
                    break;
                }

                // 引当データ存在確認
                $reserveData = $Reserve->getByMatterQuote($params['matter_id'], $params['quote_id']);
                if (count($reserveData) > 0) {
                    // 引当データがある場合、削除させない
                    $resultSts = false;
                    $resultMsg = config('message.error.quote.delete_reserve');
                    break;
                }
                
                // 売上データ存在確認
                if ($SalesDetail->existsByQuoteId($params['quote_id'])) {
                    // 売上明細データがある場合、削除させない
                    $resultSts = false;
                    $resultMsg = config('message.error.quote.delete_sales_detail');
                    break;
                }

                // 見積明細削除
                $QuoteDetail->physicalDeleteByQuoteNo($params['quote_no']);
                
                // 見積版削除
                $QuoteVersion->physicalDeleteByQuoteNo($params['quote_no']);

                // 見積削除
                $Quote->physicalDeleteById($params['quote_id']);

                // ロック解放
                $keys = array($params['quote_id'], $params['matter_id']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
                if (!$delLock) {
                    $resultSts = false;
                    $resultMsg = config('message.error.getlock');
                    break;
                }

                $resultSts = true;
            } while(false);

            if ($resultSts) {
                DB::commit();
                Session::flash('flash_success', config('message.success.delete'));
            } else {
                DB::rollBack();
                if ($resultMsg === '') {
                    $resultMsg = config('message.error.delete');
                }
                Session::flash('flash_error', $resultMsg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            if ($resultMsg === '') {
                $resultMsg = config('message.error.error');
            }
            Session::flash('flash_error', $resultMsg);
        }

        return \Response::json($resultSts);
    }

    /**
     * 指定印刷用データ作成
     *
     * @param Request $request
     * @return void
     */
    public function printTarget(Request $request) 
    {
        $result = array('status' => false, 'msg' => '');
        
        // リクエストデータ取得
        $params = $request->request->all();
        $params['version_list'] = json_decode($params['version_list'], true);

        // バリデーションチェック
        $this->isValid($params);

        // ファイルデータ取得
        $uploadFileList = $request->files;
        // ファイル拡張子チェック
        $this->isValidFile($request, $uploadFileList);

        $LockManage = new LockManage();
        $QuoteReportTemp = new QuoteReportTemp();

        DB::beginTransaction();
        try {

            // 登録
            $matterId = 0;        // outパラメータ
            $quoteId = 0;         // outパラメータ
            $this->saveQuote(false, $params, $matterId, $quoteId);

            // チェックを付けた見積明細ID
            $quoteDetailIdList = json_decode($params['quote_detail_ids']);

            // 登録
            $QuoteReportTemp->addIdList($params['quote_no'], $params['quote_version'], $quoteDetailIdList);

            // ロック中確認
            // 画面名から対象テーブルを取得
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            $keys = array($quoteId , $matterId);

            $lockCnt = 0;
            foreach ($tableNameList as $i => $tableName) {
                // ロックデータ取得
                $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                if (!is_null($lockData) 
                    && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                    && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                        $lockCnt++;
                }
            }

            $isOwnLock = count($tableNameList) === $lockCnt;
            
            if ($isOwnLock) {
                // ファイルアップロード
                $this->uploadFile($request, $uploadFileList, $params);

                DB::commit();
                $result['id'] = $matterId;
                $result['status'] = true;
                $result['msg'] = config('message.success.save');
            } else {
                DB::rollBack();
                $result['status'] = false;
                $result['msg'] = config('message.error.getlock');
            }
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getFirstMessage();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['msg'] === '') {
                $result['msg'] = $e->getMessage();
            }
            Log::error($e);
        }
        return \Response::json($result);
    }

    /**
     * CSVダウンロード
     *
     * @param Request $request
     * @return void
     */
    public function exportCSV(Request $request) 
    {
        $response = new StreamedResponse();
        // パラメータ取得
        $params = $request->request->all();

        // ヘッダー行作成
        $headers = array(
                        '"見積番号"'
                        , '"案件番号"'
                        , '"得意先別特別単価使用フラグ"'
                        , '"相手先担当者"'
                        , '"見積版"'
                        , '"見出し"'
                        , '"担当部門"'
                        , '"担当者"'
                        , '"見積作成日"'
                        , '"見積提出期限"'
                        , '"見積有効期限"'
                        , '"支払い条件"'
                        , '"仕入総計額"'
                        , '"売上総計"'
                        , '"粗利総計"'
                        , '"営業支援コメント"'
                        , '"稟議用コメント"'
                        , '"顧客向けコメント"'
                        , '"工事区分"'
                        // , '"大分類"'
                        // , '"中分類"'
                        // , '"用途"'
                        , '"階層名"'
                        , '"商品名"'
                        , '"型式・規格"'
                        , '"メーカー名"'
                        , '"仕入先名"'
                        , '"見積数量"'
                        , '"単位"'
                        , '"管理数"'
                        , '"管理数単位"'
                        , '"定価"'
                        , '"最小単位数"'
                        // , '"引当区分"'
                        , '"仕入区分"'
                        , '"販売区分"'
                        , '"仕入単価"'
                        , '"販売単価"'
                        , '"仕入掛率"'
                        , '"販売掛率"'
                        , '"仕入総額"'
                        , '"販売総額"'
                        , '"粗利率"'
                        , '"粗利総額"'
                        , '"備考"'
                    );

        $QuoteDetail = new QuoteDetail();
        // データ取得
        $csvData = $QuoteDetail->getCsvData($params);
        $csvData = $csvData->toArray();

        // // データの両端にダブルクオート追加
        // foreach ($csvData as $rKey => $row) {
        //     foreach ($row as $ckey => $col) {
        //         $col = mb_convert_encoding($col, 'SJIS', 'ASCII, JIS, UTF-8, SJIS');
        //         $csvData[$rKey][$ckey] = '"' . $col . '"';
        //     }
        // }

        $response->setCallBack(function() use($csvData, $headers){
            $file = new \SplFileObject('php://output', 'w');

            // ヘッダー挿入
            // foreach ($headers as $row) {
                $file->fputcsv(array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS', 'UTF-8');
                }, $headers), ",", "\n");
            // }

            // データ挿入
            foreach ($csvData as $row) {
                $file->fputcsv(array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS', 'UTF-8');
                }, $row), ",", '"');
            }
            flush();
        });
        // レスポンス作成
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/octet-stream');
        // ファイル名作成
        $fileNm = $params['quote_no'];
        $fileNm .= "_".$params['quote_version'];
        $fileNm .= "_".Carbon::now()->format('Ymd');
        $fileNm .= ".csv";
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileNm);
        $response->send();

        return $response;
    }

    /**
     * 案件入力内容確認
     *
     * @param Request $request
     * @return void
     */
    public function confirmMatter(Request $request) {
        $result = array(
            'status' => false, 
            'matter_name' => null, 
            'department_name' => null, 
            'staff_name' => null
        );
        
        // リクエストデータ取得
        $params = $request->request->all();
        // バリデーションチェック
        $this->isValidMatter($request);

        // 得意先データ取得
        $Customer = new Customer();
        $customerData = $Customer->getChargeData((int)$params['customer_id']);

        // 案件名生成
        $SystemUtil = new SystemUtil();
        $matterName = $SystemUtil->createMatterName($params['owner_name'], $customerData['customer_short_name']);


        // 案件データ取得
        $Matter = new Matter();
        $matterList = $Matter->getCombiComboList($params['customer_id'], $params['owner_name'], config('const.flg.off'));

        // 返却値セット
        $result['matter_name'] = $matterName;
        $result['department_name'] = $customerData['department_name'];
        $result['staff_name'] = $customerData['staff_name'];
        $result['matter_list'] = $matterList;
        $result['status'] = true;
        
        return \Response::json($result);
    }

    /**
     * 見積の存在確認
     *
     * @param Request $request
     * @return void
     */
    public function confirmQuote(Request $request) {
        $result = array(
            'status' => false, 
        );
        // リクエストデータ取得
        $params = $request->request->all();

        $Quote = new Quote();
        // 見積が存在しないならOK
        if (!$Quote->getByMatterNo($params)) {
            $result['status'] = true;
        }

        return \Response::json($result);
    }

    /**
     * 見積データ取得（らくらく見積）
     *
     * @param Request $request
     * @return void
     */
    public function searchQuote(Request $request) {
        $result = array(
            'status' => false, 
            'quoteLayerList' => null,
            'quoteDetailList' => null,
            'versionData' => null,
        );
        // リクエストデータ取得
        $params = $request->request->all();

        $SystemUtil = new SystemUtil();
        // $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();

        // 見積版データ取得　※使ってなさそうなのでコメントアウト
        // $quoteVerList = $QuoteVersion->getDataList($params['quote_no']);
        // $quoteVer = $quoteVerList->firstWhere('quote_version', $params['quote_version']);

        $treeDataList = [];

        // 見積階層データ取得　MEMO:getTreeDataの第3引数指定で階層データのみに絞ると、グリッドとfilter_tree_pathがずれるので指定しない
        // $treeDataList[0] = $QuoteDetail->getTreeData($params['quote_no'], $quoteVer['quote_version']);
        $treeDataList[0] = $QuoteDetail->getTreeData($params['quote_no'], $params['quote_version']);
        $SystemUtil->deleteNotLayerRecord($treeDataList[0][0]);

        // 見積明細データ取得
        // $gridData = $QuoteDetail->getDataList($params['quote_no'], $quoteVer['quote_version']);
        $gridData = $QuoteDetail->getDataList($params['quote_no'], $params['quote_version']);
        $gridData = $SystemUtil->addFilterTreePathInfo($gridData);
        foreach ($gridData as $i => $rec) {
            // 仮登録商品または1回限り登録商品は、新商品として扱う
            if (Common::isFlgOn($rec->draft_flg) || Common::isFlgOn($rec->auto_flg)) {
                $gridData[$i]['product_id'] = 0;
            }
        }

        $quoteLayerList = collect($treeDataList);
        $quoteDetailList = collect($gridData);

        $result['quoteLayerList'] = $quoteLayerList;
        $result['quoteDetailList'] = $quoteDetailList;
        $result['status'] = true;

        return \Response::json($result);
    }

    /**
     * 0版以外の保存処理
     *
     * @param array $params
     * @param [type] $quote 見積データ
     * @param int $quoteVersionId 見積版ID
     * @return int $quoteVersionId 見積版ID
     */
    private function saveQuoteVer($params, $quote, $quoteVersionId) {
        $saveResult = true;

        $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();

        // 見積版
        $quoteVerParams = [];
        $quoteVerParams['id'] = $params['quote_version_id'];
        $quoteVerParams['quote_no'] = $quote->quote_no;
        $quoteVerParams['quote_version'] = $params['quote_version'];
        $quoteVerParams['caption'] = $params['quote_version_caption'];
        $quoteVerParams['department_id'] = $params['department_id'];
        $quoteVerParams['staff_id'] = $params['staff_id'];
        $quoteVerParams['quote_limit_date'] = $params['quote_limit_date'];
        $quoteVerParams['quote_enabled_limit_date'] = $params['quote_enabled_limit_date'];
        $quoteVerParams['payment_condition'] = $params['payment_condition'];
        $quoteVerParams['cost_total'] = $params['cost_total'];
        $quoteVerParams['sales_total'] = $params['sales_total'];
        $quoteVerParams['profit_total'] = $params['profit_total'];
        $quoteVerParams['tax_rate'] = $params['tax_rate'];
        $quoteVerParams['sales_support_comment'] = $params['sales_support_comment'];
        $quoteVerParams['approval_comment'] = $params['approval_comment'];
        $quoteVerParams['customer_comment'] = $params['customer_comment'];
        $quoteVerParams['quote_create_date'] = $params['quote_create_date'];
        $isExistQuoteVersion = $QuoteVersion->isExist($quote->quote_no, $params['quote_version']);
        if ($isExistQuoteVersion) {
            // 更新
            $QuoteVersion->updateById($quoteVerParams);
        } else {
            // 登録
            $quoteVersionId = $QuoteVersion->add($quoteVerParams);
        }

        // 更新前の対象の見積版の見積明細を全取得
        $prevDetails = $QuoteDetail->getPerVersion($quote->quote_no, $params['quote_version']);
        // 配列化
        $prevDetailIds = [];
        $savedDetailIds = [];
        foreach ($prevDetails as $rec) {
            $prevDetailIds[$rec['quote_detail_id']] = $rec['quote_detail_id'];
        }

        if (isset($params['quote_detail'])) {
            // MEMO:親階層が配列上で前にいることが前提

            $details = $params['quote_detail'];

            $constructionIds = [];
            $seqNoPerParent = [];
            foreach ($details as $row) {
                // 表示用の階層パス
                $filterTreePath = $row['filter_tree_path'];
                // 表示用階層パスと見積明細IDの紐付け
                if (!empty($row['quote_detail_id'])) {
                    $savedDetailIds[$filterTreePath] = $row['quote_detail_id'];
                }

                // 表示用階層パスを分解
                $filterTreePathIds = explode(config('const.treePathSeparator'), $filterTreePath);

                $constructionId = 0;
                $parentQuoteDetailId = 0;
                $filterParentTreePath = '0';
                $treePath = '0';
                if (count($filterTreePathIds) >= 2) {
                    $filterParentTreePath = '';
                    $treePath = '';
                    for ($i = 0; $i <= count($filterTreePathIds) - 2; $i++) {
                        if ($filterParentTreePath != '') {
                            $filterParentTreePath .= config('const.treePathSeparator');
                            $treePath .= config('const.treePathSeparator');
                        }
                        // 親階層の表示用階層パス
                        $filterParentTreePath .= (string)$filterTreePathIds[$i];
                        // 階層パス
                        $treePath .= $savedDetailIds[$filterParentTreePath];

                        // 工事区分ID　　MEMO：グリッド上でずれることがあるのでセットし直す
                        if ($i === 0) {
                            if (isset($constructionIds[$treePath])) {
                                $constructionId = $constructionIds[$treePath];
                            }
                        }
                    }
                    
                    // 親見積明細ID
                    $parentQuoteDetailId = $savedDetailIds[$filterParentTreePath];
                }

                // 連番
                if (isset($seqNoPerParent[$parentQuoteDetailId])) {
                    $seqNoPerParent[$parentQuoteDetailId]++;
                } else {
                    $seqNoPerParent[$parentQuoteDetailId] = 1;
                }

                $quoteDetailData = $row;

                $quoteDetailData['quote_no'] = $quote->quote_no;
                $quoteDetailData['quote_version'] = $params['quote_version'];
                $quoteDetailData['construction_id'] = ($constructionId === 0) ? $quoteDetailData['construction_id'] : $constructionId;
                $quoteDetailData['parent_quote_detail_id'] = $parentQuoteDetailId;
                $quoteDetailData['seq_no'] = $seqNoPerParent[$parentQuoteDetailId];
                $quoteDetailData['tree_path'] = $treePath;
                $quoteDetailData['cost_makeup_rate'] = Common::roundDecimalRate($quoteDetailData['cost_makeup_rate']);
                $quoteDetailData['sales_makeup_rate'] = Common::roundDecimalRate($quoteDetailData['sales_makeup_rate']);
                $quoteDetailData['gross_profit_rate'] = Common::roundDecimalRate($quoteDetailData['gross_profit_rate']);

                // メーカー入力チェック
                if (Common::nullorBlankToZero($quoteDetailData['product_id']) !== 0 && Common::nullorBlankToZero($quoteDetailData['maker_id']) === 0) {
                    // 商品IDを持っているのにメーカーIDを持っていない場合、商品マスタから取得する（※通常はほとんど発生しない）
                    $Product = new Product();
                    $prodcutInfo = $Product->getProductMaker($quoteDetailData['product_id']);
                    if (!is_null($prodcutInfo)) {
                        $quoteDetailData['maker_id'] = $prodcutInfo['maker_id'];
                        $quoteDetailData['maker_name'] = $prodcutInfo['maker_name'];
                    }
                }
                
                if ($quoteDetailData['quote_detail_id']) {
                    // 更新
                    $saveResult = $QuoteDetail->updateByIdEx($quoteDetailData['quote_detail_id'], $quoteDetailData);
                    if (!$saveResult) {
                        throw new \Exception(config('message.error.save'));
                    }

                    // 更新した見積明細IDは更新前配列から除去
                    unset($prevDetailIds[$quoteDetailData['quote_detail_id']]);
                } else {
                    // 新規登録
                    $detailId = $QuoteDetail->addEx($quoteDetailData);

                    $savedDetailIds[$filterTreePath] = $detailId;
                }

                if ($quoteDetailData['depth'] === config('const.quoteConstructionInfo.depth')) {
                    // 一番上の階層（工事区分階層）の工事区分IDを保管
                    $constructionIds[$savedDetailIds[$filterTreePath]] = $quoteDetailData['construction_id'];
                }
            }
        }

        // 更新されなかった明細IDは画面上にないということなので削除
        if (count($prevDetailIds) > 0) {
            $QuoteDetail->deleteList($prevDetailIds);
        }

        return $quoteVersionId;
    }

    /**
     * 0版保存処理
     *
     * @param array $params
     * @param [type] $quote 見積データ
     * @param int $quoteVersionId 見積版ID
     * @param bool $isNew 新規フラグ  true:新規 false:更新
     * @return int $quoteVersionId 見積版ID
     */
    private function saveQuoteVerZero($params, $quote, $quoteVersionId, $isNew) {
        $SystemUtil = new SystemUtil();
        $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $Reserve = new Reserve();
        $ShipmentDetail = new ShipmentDetail();
        $Construction = new Construction();
        $System = new System();

        if ($isNew) {
            // 税率取得
            $taxInfo = $System->getByPeriod();
            
            // 見積版0版登録
            $quoteVerParams = [];
            $quoteVerParams['quote_no'] = $quote->quote_no;
            $quoteVerParams['quote_version'] = config('const.quoteCompVersion.number');
            $quoteVerParams['caption'] = config('const.quoteCompVersion.caption');
            $quoteVerParams['department_id'] = $params['department_id'];
            $quoteVerParams['staff_id'] = $params['staff_id'];
            $quoteVerParams['tax_rate'] = $taxInfo->tax_rate;
            $quoteVerParams['quote_create_date'] = $this->quoteVerDefault['quote_create_date'];
            $quoteVerParams['quote_enabled_limit_date'] = $this->quoteVerDefault['quote_enabled_limit_date'];
            $quoteVerParams['payment_condition'] = $this->quoteVerDefault['payment_condition'];
            $quoteVersionId = $QuoteVersion->add($quoteVerParams);

            // 追加部材用の工事区分データ取得
            $addLayer = $Construction->getAddFlgData();
            // 追加部材データを作成
            $QuoteDetail->quote_no = $quote->quote_no;
            $QuoteDetail->quote_version = config('const.quoteCompVersion.number');
            $QuoteDetail->construction_id = $addLayer['id'];
            $QuoteDetail->layer_flg = config('const.quoteConstructionInfo.layer_flg');
            $QuoteDetail->parent_quote_detail_id = config('const.quoteConstructionInfo.parent_quote_detail_id');
            $QuoteDetail->seq_no = config('const.quoteConstructionInfo.seq_no');
            $QuoteDetail->depth = config('const.quoteConstructionInfo.depth');
            $QuoteDetail->tree_path = config('const.quoteConstructionInfo.tree_path');
            $QuoteDetail->set_flg = config('const.quoteConstructionInfo.set_flg');
            $QuoteDetail->sales_use_flg = config('const.quoteConstructionInfo.sales_use_flg');
            $QuoteDetail->product_name = $addLayer['construction_name'];
            $QuoteDetail->quote_quantity = config('const.quoteConstructionInfo.quote_quantity');
            $QuoteDetail->min_quantity = config('const.quoteConstructionInfo.min_quantity');
            $QuoteDetail->order_lot_quantity = config('const.quoteConstructionInfo.order_lot_quantity');
            $QuoteDetail->set_kbn = config('const.quoteConstructionInfo.set_kbn');
            $QuoteDetail->row_print_flg = config('const.quoteConstructionInfo.row_print_flg');
            $QuoteDetail->price_print_flg = config('const.quoteConstructionInfo.price_print_flg');
            $QuoteDetail->add_flg = config('const.flg.on');
            $QuoteDetail->top_flg = config('const.quoteConstructionInfo.top_flg');
            $QuoteDetail->header = $addLayer['construction_name'];
            $QuoteDetail->to_layer_flg = config('const.quoteConstructionInfo.to_layer_flg');
            $QuoteDetail->over_quote_detail_id = 0;

            // 追加部材データ登録
            $quoteDetailParams = $QuoteDetail->toArray();
            $QuoteDetail->addEx($quoteDetailParams);
        }else{
            // 見積版
            $quoteVerParams = [];
            $quoteVerParams['id'] = $params['quote_version_id'];
            $quoteVerParams['quote_no'] = $quote->quote_no;
            $quoteVerParams['quote_version'] = $params['quote_version'];
            $quoteVerParams['caption'] = $params['quote_version_caption'];
            $quoteVerParams['quote_limit_date'] = $params['quote_limit_date'];
            $quoteVerParams['quote_enabled_limit_date'] = $params['quote_enabled_limit_date'];
            $quoteVerParams['payment_condition'] = $params['payment_condition'];
            $quoteVerParams['tax_rate'] = $params['tax_rate'];
            $quoteVerParams['sales_support_comment'] = $params['sales_support_comment'];
            $quoteVerParams['approval_comment'] = $params['approval_comment'];
            $quoteVerParams['customer_comment'] = $params['customer_comment'];
            $quoteVerParams['quote_create_date'] = $params['quote_create_date'];
            // 更新
            $QuoteVersion->updateById($quoteVerParams);

            foreach ($params['quote_detail'] as $key => $value) {
                $params['quote_detail'][$key] = (object)$value;
            }

            $gridData = $params['quote_detail'];

            // 見積もり明細 登録/更新
            $this->OrderServiceProvider->updateOrInsertQuoteDetail($quote->quote_no, $gridData, $params['customer_id']);

            // 保存後の見積明細データ取得
            $afterQuoteDetailData = $QuoteDetail->getByVer($quote->quote_no, config('const.quoteCompVersion.number'));
            $overQuoteDetailDataList = $afterQuoteDetailData->where('over_quote_detail_id', '!=', 0)->toArray();
            foreach($overQuoteDetailDataList as $record){
                // 数量超過で追加された見積明細IDの更新
                // 超過元見積明細取得
                $overQuoteDetailData    = $afterQuoteDetailData->firstWhere('id', $record['over_quote_detail_id'])->toArray();
                $record['product_id']   = $overQuoteDetailData['product_id'];
                $record['product_code'] = $overQuoteDetailData['product_code'];
                $record['product_name'] = $overQuoteDetailData['product_name'];
                $record['model']        = $overQuoteDetailData['model'];
                $record['maker_id']     = $overQuoteDetailData['maker_id'];
                $record['maker_name']   = $overQuoteDetailData['maker_name'];
                $record['supplier_id']  = $overQuoteDetailData['supplier_id'];
                $record['supplier_name']= $overQuoteDetailData['supplier_name'];
                $record['min_quantity'] = $overQuoteDetailData['min_quantity'];
                $record['order_lot_quantity']   = $overQuoteDetailData['order_lot_quantity'];
                
                $record['quantity_per_case']= $overQuoteDetailData['quantity_per_case'];
                $record['set_kbn']          = $overQuoteDetailData['set_kbn'];
                $record['class_big_id']     = $overQuoteDetailData['class_big_id'];
                $record['class_middle_id']  = $overQuoteDetailData['class_middle_id'];
                $record['class_small_id']   = $overQuoteDetailData['class_small_id'];
                $record['tree_species']     = $overQuoteDetailData['tree_species'];
                $record['grade']            = $overQuoteDetailData['grade'];
                $record['length']           = $overQuoteDetailData['length'];
                $record['thickness']        = $overQuoteDetailData['thickness'];
                $record['width']            = $overQuoteDetailData['width'];
                
                $record['unit']         = $overQuoteDetailData['unit'];
                $record['stock_unit']   = $overQuoteDetailData['stock_unit'];
                $record['regular_price']    = $overQuoteDetailData['regular_price'];
                $record['allocation_kbn']   = $overQuoteDetailData['allocation_kbn'];
                $record['cost_kbn']     = $overQuoteDetailData['cost_kbn'];
                $record['sales_kbn']    = $overQuoteDetailData['sales_kbn'];
                $record['cost_unit_price']  = $overQuoteDetailData['cost_unit_price'];
                $record['sales_unit_price'] = $overQuoteDetailData['sales_unit_price'];
                $record['cost_makeup_rate'] = $overQuoteDetailData['cost_makeup_rate'];
                $record['sales_makeup_rate']= $overQuoteDetailData['sales_makeup_rate'];
                //$record['memo']             = $overQuoteDetailData['memo'];

                // 管理数
                $record['stock_quantity']   = $SystemUtil->calcStock($record['quote_quantity'], $record['min_quantity']);
                // 仕入総額 = 数量 * 仕入単価
                $record['cost_total']       = $SystemUtil->calcTotal($record['quote_quantity'], $record['cost_unit_price'], true);
                // 販売総額 = 数量 * 販売単価
                $record['sales_total']      = $SystemUtil->calcTotal($record['quote_quantity'], $record['sales_unit_price'], false);
                // 粗利総額 = 販売総額 - 仕入総額
                $record['profit_total']     = $SystemUtil->calcProfitTotal($record['sales_total'], $record['cost_total']);
                // 粗利率 = 粗利総額 / 販売総額 * 100
                $record['gross_profit_rate']= $SystemUtil->calcRate($record['profit_total'], $record['sales_total']); 
                $QuoteDetail->updateByIdEx($record['id'], $record, $params['customer_id']);
            }

            $afterQuoteDetailData = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
            foreach($afterQuoteDetailData as $key => $record){
                // 在庫引当の削除　発注引当の更新
                $Reserve->changeOrderProduct($record['id'], $record['product_id'], $record['product_code']);
                // 出荷指示の削除　出荷指示の更新
                $ShipmentDetail->changeOrderProduct($record['id'], $record['product_id'], $record['product_code']);
            }

            // 数量超過の明細 更新削除
            $this->OrderServiceProvider->updateOverQuoteDetailData($params['quote_id'], $params['quote_no']);

            // 見積0版金額更新
            $QuoteVersion->updateTotalKng($quote->quote_no, config('const.quoteCompVersion.number'));

            // 全ての発注明細取得
            $orderDetailDataList = $QuoteDetail->getOrderDetailList($quote->quote_no, config('const.quoteCompVersion.number'));
            foreach($orderDetailDataList as $key => $record){
                // 発注明細の更新(見積明細のデータで上書き)
                $orderDetailData = [];
                $orderDetailData['id']           = $record['order_detail_id'];
                $orderDetailData['product_id']   = $record['product_id'];
                $orderDetailData['product_code'] = $record['product_code'];
                $orderDetailData['product_name'] = $record['product_name'];
                $orderDetailData['model']        = $record['model'];
                $orderDetailData['maker_id']     = $record['maker_id'];
                $orderDetailData['maker_name']   = $record['maker_name'];
                $orderDetailData['unit']         = $record['unit'];
                $orderDetailData['stock_unit']   = $record['stock_unit'];
                $orderDetailData['regular_price']= $record['regular_price'];
                $orderDetailData['sales_kbn']    = $record['sales_kbn'];
                $orderDetailData['cost_unit_price']  = $record['cost_unit_price'];
                $orderDetailData['sales_unit_price'] = $record['sales_unit_price'];
                $orderDetailData['cost_makeup_rate'] = $record['cost_makeup_rate'];
                $orderDetailData['sales_makeup_rate']= $record['sales_makeup_rate'];

                // 管理数
                $orderDetailData['stock_quantity']   = $SystemUtil->calcStock($record['order_quantity'], $record['min_quantity']);
                // 仕入総額 = 数量 * 仕入単価
                $orderDetailData['cost_total']       = $SystemUtil->calcTotal($record['order_quantity'], $orderDetailData['cost_unit_price'], true);
                // 販売総額 = 数量 * 販売単価
                $orderDetailData['sales_total']      = $SystemUtil->calcTotal($record['order_quantity'], $orderDetailData['sales_unit_price'], false);
                // 粗利総額 = 販売総額 - 仕入総額
                $orderDetailData['profit_total']     = $SystemUtil->calcProfitTotal($orderDetailData['sales_total'], $orderDetailData['cost_total']);
                // 粗利率 = 粗利総額 / 販売総額 * 100
                $orderDetailData['gross_profit_rate']= $SystemUtil->calcRate($orderDetailData['profit_total'], $orderDetailData['sales_total']); 
                $OrderDetail->updateById($orderDetailData);
            }
        
            // 発注更新（仕入総計、販売総計、粗利総計）
            $Order->updateTotalKngByQuoteNo($quote->quote_no);
        }

        return $quoteVersionId;
    }

    /**
     * ファイルアップロード
     *
     * @param Request $request
     * @param array $uploadFileList アップロードファイルリスト
     * @param array $params
     * @param bool $isExistVersion 見積版が新規or更新
     * @param int $quoteVersionId 見積版ID
     * @return void
     */
    private function uploadFile($request, $uploadFileList, $params) {
        // 新規版のファイルを先に処理する
        foreach ($params['version_list'] as $version => $verData) {
            $quoteVersionId = $verData['quote_version_id'];
            if ($verData['is_exists']) {
                // nop
            }else{
                // 新規時
                // コピーアップロード
                if (isset($verData['copy_file_flg']) && $verData['copy_file_flg']) {
                    $copyFiles = $verData['copy_upload_files'];
                    $copyVersionId = $verData['copy_version_id'];

                    // 複製元からファイルコピー
                    $newVersionPath = config('const.uploadPath.quote_version').$quoteVersionId;
                    foreach ($copyFiles as $val) {
                        $copyPath = config('const.uploadPath.quote_version').$copyVersionId.'/'.$val['file_name'];
                        Storage::makeDirectory($newVersionPath);
                        Storage::copy($copyPath, $newVersionPath.'/'.$val['file_name']);
                    }
                }

                // 新規アップロード
                foreach ($uploadFileList as $fileKey => $fileObj) {
                    // 0_uploload_file_0（版_upload_file_インデックス）
                    $explodeFileKey = explode('_', $fileKey);
                    // バージョンが一致する場合
                    if ($explodeFileKey[0] == $version) {
                        $request
                            ->file($fileKey)
                            ->storeAs(
                                config('const.uploadPath.quote_version').$quoteVersionId, 
                                $fileObj->getClientOriginalName()
                            );
                    }
                }
            }
        }
        // 更新版のファイルを処理する
        foreach ($params['version_list'] as $version => $verData) {
            $quoteVersionId = $verData['quote_version_id'];
            if ($verData['is_exists']) {
                // 更新時
                // ファイル削除
                $deleteList = [];
                foreach ($verData['delete_files'] as $delFile) {
                    $deleteList[] = config('const.uploadPath.quote_version').$quoteVersionId.'/'.$delFile;
                }
                if (count($deleteList) > 0) {
                    Storage::delete($deleteList);
                }

                // ファイルアップロード
                foreach ($uploadFileList as $fileKey => $fileObj) {
                    $explodeFileKey = explode('_', $fileKey);
                    // バージョンが一致する場合
                    if ($explodeFileKey[0] == $version) {
                        $request
                            ->file($fileKey)
                            ->storeAs(
                                config('const.uploadPath.quote_version').$quoteVersionId, 
                                $fileObj->getClientOriginalName()
                            );
                    }
                }
            }else{
                // nop
            }
        }
    }
    
    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValidMatter($request)
    {
        $this->validate($request, [
            'customer_id' => 'required',
            'owner_name' => 'required|max:255',
            'architecture_type' => 'required',
        ]);
    }

    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid($params)
    {    
        Validator::make($params, [
            'customer_id' => 'required|numeric',
            'owner_name' => 'max:255',
            'architecture_type' => 'required',
            'department_id' => 'required|numeric',
            'staff_id' => 'required|numeric',
            'person_id' => 'numeric',
            'construction_period' => 'max:255',
            'quote_report_to' => 'max:50',
            'version_list.*.quote_version' => 'required|numeric',
            'version_list.*.quote_version_caption' => 'required|max:30',
            'version_list.*.quote_enabled_limit_date' => 'max:30',
            'version_list.*.tax_rate' => 'numeric'
        ])->validate();
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
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function existsFile(Request $request, $id, $fileName)
    {
        $fileName = urldecode($fileName);
        $result = Storage::exists(config('const.uploadPath.quote_version'). $id. '/'. $fileName);
        return \Response::json($result);
    }
    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function download(Request $request, $id, $fileName)
    {
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.quote_version').$id.'/'.$fileName), $fileName, $headers);
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
        foreach ($uploadFileList as $fileKey => $fileObj) {
            $checkList = array_merge($checkList, array($fileKey => 'mimes_except:'.config('const.forbidden_extention')));
        }

        if (count($checkList) > 0) {
            return $this->validate($request, $checkList);
        } else {
            return true;
        }
    }

    /**
     * 商品情報取得
     *
     * @param Request $request
     * @return void
     */
    public function getProductsInfo(Request $request) {
        $result = array(
            'productList' => null,
            'costProductPriceList' => null, 
            'salesProductPriceList' => null, 
        );
        
        $Product = new Product();

        // リクエストデータ取得
        $params = $request->request->all();

        $productCodes = array_unique(json_decode($params['product_codes'], true));
        $tmpProductList = $Product->getByProductCodes($productCodes, null, config('const.flg.off'));
        $productIds = $tmpProductList->pluck('product_id')->toArray();

        // 商品IDから単価情報を取得する
        $priceList = $Product->getPriceData($params['customer_id'], $productIds);
        foreach ($priceList as $record) {
            if ($record->cost_sales_kbn === config('const.costSalesKbn.cost')) {
                // 仕入単価
                $result['costProductPriceList'][$record->product_id][$record->unit_price_kbn] = $record;
            } else {
                // 販売単価
                $result['salesProductPriceList'][$record->product_id][$record->unit_price_kbn] = $record;
            }
        }

        $productList = [];
        foreach($tmpProductList as $product){
            if (!isset($productList[$product['product_code']])) {
                $productList[$product['product_code']] = [];
            }
            if (!isset($productList[$product['product_code']][$product['maker_id']])) {
                $productList[$product['product_code']][$product['maker_id']] = $product;
            }
        }
        $result['productList'] = $productList;

        return \Response::json($result);
    }

}
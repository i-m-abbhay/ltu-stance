<?php
namespace App\Http\Controllers;

use DB;
use Session;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Supplier;
use App\Models\ClassBig;
use App\Models\Construction;
use Storage;
use Carbon\Carbon;
use App\Models\LockManage;
use App\Models\SupplierMaker;

/**
 * 仕入先メーカー対照マスタ
 */
class SupplierMakerContrastController extends Controller
{
    const SCREEN_NAME = 'supplier-maker-contrast';

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
     * @return type
     */
    public function index(Request $request, $id = null)
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        // リスト取得
        $Supplier = new Supplier();
        $LockManage = new LockManage();
        $SupplierMaker = new SupplierMaker();
        $lockData = null;
        $kbn[] = config('const.supplierMakerKbn.supplier');
        $supplierList = $Supplier->getBySupplierKbn($kbn);
        $ClassBig = new ClassBig();
        $bigList = $ClassBig->getComboList();

        $Construction = new Construction();
        $constList = $Construction->getComboList();

        // 取扱品目、施工業者区分JOIN
        foreach ($supplierList as $key => $row) {
            // 取扱品目
            if (!empty($row['product_line'])) {
                // 先頭文字("[")、末尾文字("]")、空白を排除
                $pLines = $row['product_line'];
                $pLines = str_replace(array(" ", "　"), "", $pLines);
                $pLines = substr($pLines , 1, strlen($pLines)-2);
                // 配列化              
                $pLines = explode(',', $pLines);
                for ($i = 0; $i < count($pLines); $i++) {
                    $pLines[$i] = (int)$pLines[$i];
                    foreach ($bigList as $cBig) {
                        if ($pLines[$i] == $cBig['id']) {
                            $pLines[$i] = $cBig['class_big_name'];
                        }
                    }                    
                }
                $pLines = implode('／', $pLines);
                $row['product_line'] = $pLines;
            }
            // 施工業者区分
            if (!empty($row['contractor_kbn'])) {
                // 先頭文字("[")、末尾文字("]")、空白を排除
                $cKbn = $row['contractor_kbn'];
                $cKbn = str_replace(array(" ", "　"), "", $cKbn);
                $cKbn = substr($cKbn , 1, strlen($cKbn)-2);
                // 配列化              
                $cKbn = explode(',', $cKbn);
                for ($i = 0; $i < count($cKbn); $i++) {
                    $cKbn[$i] = (int)$cKbn[$i];
                    foreach ($constList as $cList) {
                        if ($cKbn[$i] == $cList['id']) {
                            $cKbn[$i] = $cList['construction_name'];
                        }
                    }                    
                }
                $cKbn = implode('／', $cKbn);
                $row['contractor_kbn'] = $cKbn;
            }
        }

        try {
            if (is_null($id)) {
                // 新規
                $supplierData = $Supplier;
                $contrastData = $SupplierMaker;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $supplierData = $Supplier->getById($id);
                $contrastData = $SupplierMaker->getByMakerId($id);

                // 取扱品目、施工業者区分JOIN
                foreach ($contrastData as $key => $row) {
                    // 取扱品目
                    if (!empty($row['product_line'])) {
                        // 先頭文字("[")、末尾文字("]")、空白を排除
                        $pLines = $row['product_line'];
                        $pLines = str_replace(array(" ", "　"), "", $pLines);
                        $pLines = substr($pLines , 1, strlen($pLines)-2);
                        // 配列化              
                        $pLines = explode(',', $pLines);
                        for ($i = 0; $i < count($pLines); $i++) {
                            $pLines[$i] = (int)$pLines[$i];
                            foreach ($bigList as $cBig) {
                                if ($pLines[$i] == $cBig['id']) {
                                    $pLines[$i] = $cBig['class_big_name'];
                                }
                            }                    
                        }
                        $pLines = implode('／', $pLines);
                        $row['product_line'] = $pLines;
                    }
                    // 施工業者区分
                    if (!empty($row['contractor_kbn'])) {
                        // 先頭文字("[")、末尾文字("]")、空白を排除
                        $cKbn = $row['contractor_kbn'];
                        $cKbn = str_replace(array(" ", "　"), "", $cKbn);
                        $cKbn = substr($cKbn , 1, strlen($cKbn)-2);
                        // 配列化              
                        $cKbn = explode(',', $cKbn);
                        for ($i = 0; $i < count($cKbn); $i++) {
                            $cKbn[$i] = (int)$cKbn[$i];
                            foreach ($constList as $cList) {
                                if ($cKbn[$i] == $cList['id']) {
                                    $cKbn[$i] = $cList['construction_name'];
                                }
                            }                    
                        }
                        $cKbn = implode('／', $cKbn);
                        $row['contractor_kbn'] = $cKbn;
                    }
                }

                // 編集権限がある場合
                if ($isEditable === config('const.authority.has')) {
                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                    $keys = array($id);

                    DB::beginTransaction();
                    $lockCnt = 0;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // GETパラメータからモード取得
                        $mode = $request->query(config('const.query.mode'));
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
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                }

            }

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Supplier.supplier-maker-contrast')
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('supplierData', $supplierData)
                ->with('supplierList', $supplierList)
                ->with('contrastData', $contrastData)
                ->with('bigList', $bigList)
                ->with('constList', $constList)
                ;
    }
    
    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        $result = false;
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            // 一覧データ取得
            $Supplier = new Supplier();
            $kbn[] = config('const.supplierMakerKbn.supplier');
            $list = $Supplier->getBySupplierKbn($kbn);

            // 大分類データ取得
            $ClassBig = new ClassBig();
            $bigList = $ClassBig->getComboList();
            $bigList = $bigList->keyBy('id')->toArray();
            // 工事区分データ取得
            $Construction = new Construction();
            $constList = $Construction->getComboList();
            $constList = $constList->keyBy('id')->toArray();

            // 取扱品目、施工業者区分JOIN
            foreach ($list as $key => $row) {
                // 取扱品目
                if (!empty($row['product_line'])) {
                    // 先頭文字("[")、末尾文字("]")、空白を排除
                    $pLines = $row['product_line'];
                    $pLines = str_replace(array(" ", "　"), "", $pLines);
                    $pLines = substr($pLines , 1, strlen($pLines)-2);
                    // 配列化              
                    $pLines = explode(',', $pLines);
                    for ($i = 0; $i < count($pLines); $i++) {
                        $pLines[$i] = (int)$pLines[$i];
                        foreach ($bigList as $cBig) {
                            if ($pLines[$i] == $cBig['id']) {
                                $pLines[$i] = $cBig['class_big_name'];
                            }
                        }                    
                    }
                    $pLines = implode('／', $pLines);
                    $row['product_line'] = $pLines;
                }
                // 施工業者区分
                if (!empty($row['contractor_kbn'])) {
                    // 先頭文字("[")、末尾文字("]")、空白を排除
                    $cKbn = $row['contractor_kbn'];
                    $cKbn = str_replace(array(" ", "　"), "", $cKbn);
                    $cKbn = substr($cKbn , 1, strlen($cKbn)-2);
                    // 配列化              
                    $cKbn = explode(',', $cKbn);
                    for ($i = 0; $i < count($cKbn); $i++) {
                        $cKbn[$i] = (int)$cKbn[$i];
                        foreach ($constList as $cList) {
                            if ($cKbn[$i] == $cList['id']) {
                                $cKbn[$i] = $cList['construction_name'];
                            }
                        }                    
                    }
                    $cKbn = implode('／', $cKbn);
                    $row['contractor_kbn'] = $cKbn;
                }
            }
            // 仕入先名 部分一致で返す
            if (!empty($params['supplier_name'])) {
                $list = collect($list)->filter(function ($item) use ($params) {
                    return false !== stristr($item->supplier_name, $params['supplier_name']);
                });
            }
            // 取扱品目 部分一致で返す
            if (!empty($params['product_line'])) {
                $list = collect($list)->filter(function ($item) use ($params) {
                    return false !== stristr($item->product_line, $params['product_line']);
                });
            }
            // 施工業者区分 部分一致で返す
            if (!empty($params['construction_name'])) {
                $list = collect($list)->filter(function ($item) use ($params) {
                    return false !== stristr($item->contractor_kbn, $params['construction_name']);
                });
            }
            // filterで乱れた添字振り直し
            $list = array_values($list->toArray());          

            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $rtnList = array('status' => false, 'msg' => '', 'url' => \Session::get('url.intended', url('/')));

        // 権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        // リクエストデータ取得
        $params = $request->all();

        $newFlg = false;
        $delFlg = false;
        if (empty($params['maker_id'])) {
            $newFlg = true;
        }
        if (!empty($params['delete_supplier'])) {
            $delFlg = true;
        }


        DB::beginTransaction();
        try{
            $SupplierMaker = new SupplierMaker(); 

            $saveResult = false;
            $delLock = false;
            if ($newFlg && !$delFlg) {
                // 登録
                foreach ($params['supplier'] as  $key => $val) {
                    $SupplierMaker->add($params['supplier'][$key]);
                }

                $delLock = true;
                $saveResult = true;
            } else {
                // 更新
                $makerId = $params['maker_id'];
                if ($delFlg) {
                    foreach ($params['delete_supplier'] as $key => $val) {
                        // 削除
                        $SupplierMaker->deleteBySupMakerId($params['delete_supplier'][$key]);
                    }
                }

                if (!empty($params['supplier'])) {
                    foreach ($params['supplier'] as  $key => $val) {
                        // 新規or更新
                        $SupplierMaker->upsert($params['supplier'][$key]);
                    }
                }
               
                // ロック解放
                $keys = array($params['maker_id']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

                $saveResult = true;
            }
            
            if ($saveResult && $delLock) {
                DB::commit();
                $rtnList['status'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));  
            }
        } catch (\Exception $e) {
            DB::rollback();
            $rtnList['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($rtnList);
    }

}
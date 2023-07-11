<?php
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Base;
use DB;
use Session;
use Illuminate\Support\Facades\Log;
use Auth;
use Carbon\Carbon;
use App\Models\Authority;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class WarehouseEditController extends Controller
{
    const SCREEN_NAME = 'warehouse-edit';
    
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }



    /**
     *  初期表示
     *  @params $id 倉庫ID
     *  @return type
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

        $Warehouse = new Warehouse();
        $warehouseData = null;
        $LockManage = new LockManage();
        $lockData = null;

        try{
            if (is_null($id)) {
                // 新規
                $warehouseData = $Warehouse;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $warehouseData = $Warehouse->getById($id);
                // owner_kbnが仕入先(2)の場合NotFoundHttpException
                if ($warehouseData['owner_kbn'] == config('const.ownerKbn.supplier')){
                    throw new NotFoundHttpException();
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

        // 拠点リスト取得
        $Base = new Base();
        $baseList = $Base->getList(array());

        return view('Warehouse.warehouse-edit')
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('warehouseData', $warehouseData)
                ->with('baseList', $baseList)
                ;
    }


     /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->request->all();

            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }
            // バリデーションチェック
            $this->isValid($request);
        }
        

        DB::beginTransaction();
        $Warehouse = new Warehouse();
        try {
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;
            $delLock = false;
            if ($newFlg) {
                // 登録
                $ownerKbn = config('const.ownerKbn.company');
                $saveResult = $Warehouse->add($params, $params['base_id'], $ownerKbn);
                $delLock = true;
            } else {
                // 更新
                $saveResult = $Warehouse->updateById($params, $params['base_id']);

                // ロック解放
                $keys = array($params['id']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            }
            
            if ($saveResult && $delLock) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
    

    /**
     * 削除
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            // 編集権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
                throw new \Exception();
            }
            $Warehouse = new Warehouse();

            // 削除
            $deleteResult = $Warehouse->deleteById($params['id']);

            // ロック解放
            $keys = array($params['id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

            if ($deleteResult && $delLock) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.delete'));
            } else {
                throw new \Exception(config('message.error.delete'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * バリデーションチェック
     *
     * @param \App\Http\Controllers\Request $request
     * @return boolean
     */
    public function isValid(Request $request) 
    {   
        
        $this->validate($request, [
            'warehouse_name' => 'required|max:30',
            'warehouse_short_name' => 'max:10',
            'base_id' => 'required|numeric',
            'owner_id' => 'numeric',
            'owner_kbn' => 'numeric',
            'zipcode' => 'max:7',
            'address1' => 'max:50',
            'address2' => 'max:50',
            'latitude_jp' => 'numeric|nullable',
            'longitude_jp' => 'numeric|nullable',
            'latitude_world' => 'numeric|nullable',
            'longitude_world' => 'numeric|nullable',
            // 'truck_flg' => 'numeric',
        ]);
    }  
}
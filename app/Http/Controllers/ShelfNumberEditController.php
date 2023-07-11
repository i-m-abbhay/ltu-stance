<?php
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\ShelfNumber;
use DB;
use App\Libs\Common;
use Session;
use Illuminate\Support\Facades\Log;
use Auth;
use Carbon\Carbon;
use App\Models\Authority;
use App\Models\Base;
use App\Models\LockManage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ShelfNumberEditController extends Controller
{
    const SCREEN_NAME = 'shelf-number-edit';
    
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
     *  @params $id 棚番ID
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

        $ShelfNumber = new ShelfNumber();
        $shelfNumberList = null;
        $Warehouse = new Warehouse();
        $warehouseData = null;
        $Base = new Base();
        $baseData = null;
        $LockManage = new LockManage();
        $lockData = null;

        try{
            if (!is_null($id)) {
                // 編集
                $id = (int)$id;
                // データ取得
                $shelfNumberList = $ShelfNumber->getByWarehouseId($id);
                $warehouseData = $Warehouse->getById($id);
                $baseData = $Base->getById($warehouseData['owner_id']);

                $isShelfLock = config('const.flg.off');
                $isExist = $ShelfNumber->isExistReturnShelf($baseData['id']);
                if ($isExist || $baseData == null) {
                    // 返品棚ロック
                    $isShelfLock = config('const.flg.on');
                }

                // if ($baseData == null) {
                //     // 拠点が未設定
                //     $baseData = $Base;
                //     Common::initModelProperty($baseData);
                // }

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

        return view('Shelf.shelf-number-edit')
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('isShelfLock', $isShelfLock)
                ->with('shelfNumberList', $shelfNumberList)
                ->with('warehouseData', $warehouseData)
                ->with('baseData', $baseData)
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
            $shelfList = json_decode($params['shelfList'], true);

            // バリデーションチェック
            $this->isValid($request);
        }
        

        DB::beginTransaction();
        $ShelfNumber = new ShelfNumber();
        try {
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            foreach ($shelfList as $key => $val) {
                $saveResult = false;
                $delLock = false;
                $newFlg = false;
                $delFlg = false;
                if (empty($shelfList[$key]['id'])) {
                    $newFlg = true;
                }
                if ($shelfList[$key]['del_flg'] == config('const.flg.on')) {
                    $delFlg = true;
                }
                $shelfList[$key]['warehouse_id'] = $params['warehouse_id']; 

                if ($newFlg && !$delFlg) {
                    // 登録
                    $ShelfNumber->add($shelfList[$key]);
                    $saveResult = true;
                } else if (!$newFlg && !$delFlg) {
                    // 更新
                    $ShelfNumber->updateById($shelfList[$key]);
                    $saveResult = true;
                } else if($delFlg) {
                    // 削除
                    $ShelfNumber->deleteById($shelfList[$key]['id']);
                    $saveResult = true;
                }
            }
            // ロック解放
            $keys = array($params['warehouse_id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            
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
            $ShelfNumber = new ShelfNumber();

            // 削除
            $deleteResult = $ShelfNumber->deleteByWarehouseId($params['id']);

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
     * 棚CSV出力
     *
     * @param Request $request
     * @return void
     */
    public function export(Request $request)
    {
        $response = new StreamedResponse();
        // パラメータ取得
        $params = $request->request->all();

        // ヘッダー行作成
        $columns[] = array(
                '"棚ID"'
                , '"拠点名"'
                , '"倉庫名"'
                , '"倉庫名略称"'
                , '"棚番(エリア)"'
                , '"棚番(段数)"'
                , '"棚種別"'
        );

        $ShelfNumber = new ShelfNumber();
        // データ取得
        $csvData = $ShelfNumber->getCsvData($params['warehouse_id']);
        $csvData = $csvData->toArray();

        // データの両端にダブルクオート追加
        // foreach ($csvData as $rKey => $row) {
        //     foreach ($row as $ckey => $col) {
        //         $col = mb_convert_encoding($col, 'SJIS', 'ASCII, JIS, UTF-8, SJIS');
        //         $csvData[$rKey][$ckey] = '"' . $col . '"';
        //     }
        // }

        $response->setCallBack(function() use($csvData, $columns){
            $file = new \SplFileObject('php://output', 'w');

            // ヘッダー挿入
            foreach ($columns as $row) {
                $file->fputcsv(array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS-win', 'ASCII, JIS, UTF-8, SJIS');
                }, $row), ",", "\n");
            }

            // データ挿入
            foreach ($csvData as $row) {
                $file->fputcsv(array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS-win', 'ASCII, JIS, UTF-8, SJIS');
                }, $row), ",");
            }
            flush();
        });
        // レスポンス作成
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/octet-stream');
        // ファイル名作成
        $fileNm = 'ShelfList_';
        $fileNm .= $params['warehouse_id'].'_';
        $fileNm .= Carbon::now()->format('Ymd');
        $fileNm .= ".csv";
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileNm);
        $response->send();

        return $response;

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
        ]);
    }  
}
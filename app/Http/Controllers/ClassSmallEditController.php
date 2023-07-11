<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Base;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Models\ClassSmall;
use App\Models\Construction;

/**
 * 工程編集
 */
class ClassSmallEditController extends Controller
{
    const SCREEN_NAME = 'class-small-edit';

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
     * @param Request $request
     * @param $id 拠点ID
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

        $ClassSmall = new ClassSmall();
        $classSmallData = null;
        $LockManage = new LockManage();
        $lockData = null;
        $Construction = new Construction();
        $constructionList = $Construction->getComboList(false);
        $classSmallList = $ClassSmall->getComboList();

        try {
            if (is_null($id)) {
                // 新規
                $classSmallData = $ClassSmall;
                Common::initModelProperty($classSmallData);
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $classSmallList = $classSmallList->whereNotIn('id', array($id))->toArray();
                $classSmallList = array_values($classSmallList);
                $classSmallData = $ClassSmall->getById($id);
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

        return view('ClassSmall.class-small-edit') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('classSmallData', $classSmallData)
                ->with('constructionList', $constructionList)
                ->with('classSmallList', json_encode($classSmallList))
                ;
    }
    
    /**
     * 保存
     * @param Request $request
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

            $exType = config('const.flg.off');
            if (!empty($params['base_date_type'])) {
                $exType = config('const.flg.on');
                $params['base_class_small_id'] = null;
            }
            $this->isBaseTypeValid($request, $exType);
        }

        DB::beginTransaction();
        $ClassSmall = new ClassSmall();
        try {
            if (!$hasEditAuth) {
                // 編集権限なし
                throw new \Exception();
            }

            $saveResult = false;
            $delLock = false;
            if ($newFlg) {
                // 登録
                $saveResult = $ClassSmall->add($params);
                $delLock = true;
            } else {
                // 更新
                $saveResult = $ClassSmall->updateById($params);
                
                // ロック解放
                $keys = array($params['id']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            }
            
            if ($saveResult && $delLock) {
                DB::commit();
                $resultSts= true;
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
     * @param Request $request
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

            $ClassSmall = new ClassSmall();

            // 削除
            $deleteResult = $ClassSmall->deleteById($params['id']);

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
     * @param Request $request
     * @return type
     */
    private function isValid($request)
    {
        $this->validate($request, [
            'construction_id' => 'required|numeric',
            'class_small_name' => 'required|max:20',
            'construction_period_days' => 'required|numeric|max:2147483647|min:1',
            'start_date_calc_days' => 'required|numeric|max:2147483647',
            'order_timing' => 'required|numeric|max:2147483647|min:0',
        ]);
    }

    /**
     * バリデーションチェック
     * 
     * @param Request $request
     * @param $exType 基準日種別　true: 入力有 false: 入力無
     * @return type
     */
    private function isBaseTypeValid($request, $exType)
    {
        if ($exType) {
            $this->validate($request, [
                'base_date_type' => 'required|numeric',
            ]);
        } else {
            $this->validate($request, [
                'base_class_small_id' => 'required|numeric',
            ]);
        }
    }

}
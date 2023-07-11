<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;
use App\Models\Supplier;
use App\Models\LockManage;
use App\Models\SupplierFile;
use App\Models\SupplierMaker;
use Carbon\Carbon;

/**
 * 仕入先／メーカー価格ファイル
 */
class SupplierFileController extends Controller
{
    const SCREEN_NAME = 'supplier-file';

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

        $Supplier = new Supplier();
        $SupplierFile = new SupplierFile();
        $LockManage = new LockManage();
        $lockData = null;
        $supplierData = null;
        $fileList = null;

        try {
            if (is_null($id)) {
                throw new NotFoundHttpException();
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $supplierData = $Supplier->getById($id);
                $fileList = $SupplierFile->getFileListBySupplierId($id);


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
        

        return view('Supplier.supplier-file') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('supplierData', $supplierData)
                ->with('fileList', $fileList)
                ;
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $rtnResult = false;

        // 権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        // リクエストデータ取得
        $params = $request->all();

        // バリデーションチェック
        $this->isValid($request);
        $this->fileValid($request);

        DB::beginTransaction();        
        try{
            $SupplierFile = new SupplierFile(); 
            $saveResult = false;
            $delLock = false;

            $fileNm = '';
            $uploadPath = config('const.uploadPath.supplier_file'). $params['supplier_id']. '/';
            // ファイルアップロード
            if ($request->hasFile('file')) {
                // ファイルあり
                $file = $request->file('file');
                $startDate = str_replace('/', '', $params['start_date']);
                $fileExtension = $file->getClientOriginalExtension();
                $name = str_replace('.'.$fileExtension, '', $file->getClientOriginalName());

                $fileNm = $name. '_'. $startDate .'.'. $fileExtension;

                $file->storeAs($uploadPath, $fileNm);
            }
            $params['file_name'] = $fileNm;
            // 登録
            $newSupplierId = $SupplierFile->add($params);
            

            $saveResult = true;

            // ロック解放
            $keys = array($params['supplier_id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

            $saveResult = true;
            
            if ($saveResult && $delLock) {
                DB::commit();
                $rtnResult = false;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.delete'));
            }
        } catch (\Exception $e) {
            DB::rollback();
            $rtnResult = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($rtnResult);
    }

    /**
     * 論理削除
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
            // 権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
                throw new NotFoundHttpException();
            }

            $SupplierFile = new SupplierFile();

            // 削除
            $deleteResult = $SupplierFile->deleteById($params['id']);

            // TODO: Storageのファイルも削除
            $uploadPath = config('const.uploadPath.supplier_file'). $params['supplier_id']. '/'. $params['file_name'];
            Storage::delete($uploadPath);

            // ロック解放
            $keys = array($params['supplier_id']);
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
     * メーカー仕入先価格ファイルを表示（PDF）
     * 
     * @param Request $request
     */
    protected function openFile($supplier_file_id)
    {
        $SupplierFile = new SupplierFile();

        try {
            $fileData = $SupplierFile->getById($supplier_file_id);
            $fileName = $fileData->file_name;
            $supplier_id = $fileData->supplier_id;
            $file = Storage::get(config('const.uploadPath.supplier_file'). $supplier_id. '/'. $fileName);
            return response($file, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
    }



    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request) 
    {
        $this->validate($request, [
            'supplier_id' => 'required',
            'start_date' => 'required|unique:m_supplier_file,start_date,,,start_date,'. $request->request->get('start_date'). ',supplier_id,'. $request->request->get('supplier_id'),
        ]);
    }

    protected function fileValid(Request $request) 
    {
        $this->validate($request, [
            'file' => 'required|file|mimes:pdf',
        ]);
    }
}

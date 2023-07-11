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
use App\Models\Construction;
use Auth;
use App\Models\General;
use App\Models\Supplier;
use App\Models\Person;
use App\Models\Warehouse;
use App\Models\LockManage;
use App\Models\ClassBig;
use App\Models\OwnBank;
use App\Models\Bank;
use App\Models\SupplierMaker;
use Carbon\Carbon;

/**
 * 仕入先マスタ
 */
class SupplierEditController extends Controller
{
    const SCREEN_NAME = 'supplier-edit';

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
        $Person = new Person();
        $Warehouse = new Warehouse();
        $LockManage = new LockManage();
        $lockData = null;
        $supplierData = null;
        $personData = null;
        $warehouseData = null;
        $ClassBig = new ClassBig();
        $Construction = new Construction();
        $classBigList = $ClassBig->getComboList();
        $constList = $Construction->getComboList();
        $General = new General();
        // 手数料区分取得
        $kbn = config('const.general.fee');
        $feeKbn = $General->getCategoryList($kbn);
        // 支払サイト取得
        $kbn = config('const.general.paysight');
        $paySight = $General->getCategoryList($kbn);
        // 口座種別取得
        $kbn = config('const.general.account');
        $accKbn = $General->getCategoryList($kbn);
        // 法人格取得
        $kbn = config('const.general.juridical');
        $juridList = $General->getCategoryList($kbn);

        $Bank = new Bank();
        $bankList = $Bank->getBankList();
        $branchList = $Bank->getBankBranchList();
        // $kbn = [config('const.supplierMakerKbn.supplier')];
        $supplierList = $Supplier->getComboList();

        // 仕入先メーカー対照マスタの存在チェック
        $isExists = (new SupplierMaker())->isExist($id);
        $isKbnLock = config('const.flg.off');
        if ($isExists) {
            $isKbnLock = config('const.flg.on');
        }

        try {
            if (is_null($id)) {
                // 新規
                $supplierData = $Supplier;
                $personData = $Person;
                $warehouseData = $Warehouse;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $supplierData = $Supplier->getById($id);
                $type = config('const.person_type.supplier');
                $personData = $Person->getByCompanyId($id, $type);
                $ownerKbn = config('const.ownerKbn.supplier');
                $warehouseData = $Warehouse->getByOwnerId($id, $ownerKbn);

                foreach($personData as $data) {
                    if(isset($data['image_file']) && $data['image_file'] !== ''){
                        $path = config('const.uploadPath.person_supplier').$data['id'].'/'.$data['image_file'];
                        $data['image_file'] = 'data:image/png;base64,'.base64_encode(Storage::get($path));
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
        

        return view('Supplier.supplier-edit') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('supplierData', $supplierData)
                ->with('personData', $personData)
                ->with('warehouseData', $warehouseData)
                ->with('classBigList', $classBigList)
                ->with('constList', $constList)
                ->with('feeKbn', $feeKbn)
                ->with('accKbn', $accKbn)
                ->with('paySight', $paySight)
                ->with('bankList', $bankList)
                ->with('branchList', $branchList)
                ->with('supplierList', $supplierList)
                ->with('juridList', $juridList)
                ->with('isKbnLock', $isKbnLock)
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
        $rtnList = array('status' => false, 'msg' => '', 'url' => \Session::get('url.intended', url('/')));

        // 権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        // リクエストデータ取得
        $params = $request->all();
        // 各リスト取得
        $persons = $params['person'];
        $houseList = $params['warehouse'];
        
        $uploadPath = config('const.uploadPath.person_supplier');

        $newFlg = false;
        if (empty($params['id'])) {
            $newFlg = true;
        }

        // []で囲む
        if (!empty($params['product_line'])) {
            $params['product_line'] = '[' . $params['product_line'] . ']';
        }
        if (!empty($params['contractor_kbn'])) {
            $params['contractor_kbn'] = '[' . $params['contractor_kbn'] . ']';
        }

        // バリデーションチェック
        if($params['supplier_maker_kbn'] == config('const.supplierMakerKbn.maker')){
            $this->isMakerValid($request);
        } else {
            $this->isValid($request);

            if (!empty($params['transfer_rate'])) {
                // 振込割合が0以外
                $this->isTransferValid($request);
            }
    
            if (!empty($params['bill_rate'])) {
                // 手形割合が0以外
                $this->isBillValid($request);
            }
        }

        DB::beginTransaction();        
        try{
            $Supplier = new Supplier(); 
            $Person = new Person();
            $Warehouse = new Warehouse();
            $saveResult = false;
            $delLock = false;
            if ($newFlg) {
                // 登録
                $newSupplierId = $Supplier->add($params);
                // 種別:仕入先
                $type = config('const.person_type.supplier');
                // パーソン登録
                foreach($persons as $ps){
                    if(!empty($ps['name'])){
                        if(isset($ps['file'])){
                            $fileName = $ps['file']->getClientOriginalName();
                        }else {
                            $fileName = null;
                        }
                        $newPersonId = $Person->add($ps, $type, $newSupplierId, $fileName);
                        if(!empty($ps['file'])){
                            $extension = $ps['file']->getClientOriginalExtension();
                            $ps['file']->storeAs($uploadPath.$newPersonId, $fileName);
                        }
                    }
                }
                // 倉庫登録
                $ownerKbn = config('const.ownerKbn.supplier');
                foreach($houseList as $whouse){
                    if(!empty($whouse['warehouse_name'])){
                        $Warehouse->add($whouse, $newSupplierId, $ownerKbn);
                    }
                }
                $delLock = true;
                $saveResult = true;
            } else {
                // 更新
                $supplierId = $params['id'];
                $Supplier->updateById($params);
                // パーソン登録
                foreach($persons as $ps){
                    $type = config('const.person_type.supplier');
                    
                    if(!empty($ps['id']) && !empty($ps['name'])){
                        // 既に登録されているパーソン
                        // if(!empty($ps['file']) || $ps['file'] !== 'null' || $ps['file'] !== null){
                        if(isset($ps['file'])){
                            $fileName = $ps['file']->getClientOriginalName();
                        }else {
                            $fileName = null;
                        }
                        $Person->updateById($ps, $fileName);

                        if(!empty($ps['file']) && $ps['file'] !== null){
                            Storage::deleteDirectory($uploadPath.$ps['id'], $ps['id']);
                            $ps['file']->storeAs($uploadPath.$ps['id'], $fileName);
                        }
                    } else if (!empty($ps['name'])){
                        // 新規で登録されたパーソン
                        if(!empty($ps['file']) && $ps['file'] !== null){
                            $fileName = $ps['file']->getClientOriginalName();
                        }else {
                            $fileName = '';
                        }
                        $ps['id'] = $Person->add($ps, $type, $supplierId, $fileName);

                        if(!empty($ps['file']) && $ps['file'] !== null){
                            Storage::deleteDirectory($uploadPath.$ps['id'], $ps['id']);
                            $ps['file']->storeAs($uploadPath.$ps['id'], $fileName);
                        }
                    }
                }
                // 倉庫登録
                $ownerKbn = config('const.ownerKbn.supplier');
                foreach($houseList as $whouse){
                    if(!empty($whouse['id']) && !empty($whouse['warehouse_name'])){
                        // 既に登録されている倉庫
                        $Warehouse->updateById($whouse, $supplierId);
                    }else if(!empty($whouse['warehouse_name'])){
                        // 新規で登録された倉庫
                        $Warehouse->add($whouse, $supplierId, $ownerKbn);
                    }
                }
                // ロック解放
                $keys = array($params['id']);
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

            $Supplier = new Supplier();

            // 削除
            $deleteResult = $Supplier->deleteById($params['id']);

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
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request) 
    {
        $this->validate($request, [
            'supplier_name' => 'required|max:50',
            'supplier_short_name' => 'max:30',
            'supplier_kana' => 'max:30',
            'corporate_number' => 'max:13',
            'supplier_maker_kbn' => 'required',
            'zipcode' => 'max:7',
            'address1' => 'max:50',
            'address2' => 'max:50',
            'tel' => 'max:20',
            'fax' => 'max:20',
            'email' => 'max:100',
            'account_number' => 'max:8',
            'account_name' => 'max:100',
            'closing_day' => 'required|numeric',
            'payment_sight' => 'required|numeric',
            'payment_day' => 'required|numeric',
            'cash_rate' => 'required|numeric',
            'check_rate' => 'required|numeric',
            'bill_rate' => 'required|numeric',
            'transfer_rate' => 'required|numeric',
            'bill_min_price' => 'nullable|numeric|max:999999999',
            'bill_fee' => 'nullable|numeric|max:999999999',
            'safety_org_cost' => 'required|numeric',
            'receive_rebate' => 'required|numeric',
            'sponsor_cost' => 'required|numeric|max:999999999',
        ]);
    }

    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isMakerValid(Request $request) 
    {
        $this->validate($request, [
            'supplier_name' => 'required|max:50',
            'supplier_short_name' => 'max:30',
            'supplier_kana' => 'max:30',
            'corporate_number' => 'max:13',
            'supplier_maker_kbn' => 'required',
            'zipcode' => 'max:7',
            'address1' => 'max:50',
            'address2' => 'max:50',
            'tel' => 'max:20',
            'fax' => 'max:20',
            'email' => 'max:100',
            'account_number' => 'max:8',
            'account_name' => 'max:100',
            'cash_rate' => 'nullable|numeric',
            'check_rate' => 'nullable|numeric',
            'bill_rate' => 'nullable|numeric',
            'transfer_rate' => 'nullable|numeric',
            'bill_min_price' => 'nullable|numeric|max:999999999',
            'bill_fee' => 'nullable|numeric|max:999999999',
        ]);
    }


    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isTransferValid(Request $request) 
    {
        $this->validate($request, [
            'bank_code' => 'required',
            'branch_code' => 'required',
            'account_type' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
        ]);
    }

    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isBillValid(Request $request) 
    {
        $this->validate($request, [
            'bill_min_price' => 'required',
            'bill_deadline' => 'required',
            'bill_fee' => 'required',
            'bill_issuing_bank_id' => 'required',
        ]);
    }

    protected function imageValid(Request $request) 
    {
        $this->validate($request, [
            'file' => 'required|file|image|mimes:jpeg,png,jpg',
        ]);
    }
}

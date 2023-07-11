<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Person;
use App\Models\CustomerBranch;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use App\Models\Department;
use Auth;
use App\Models\LockManage;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Supplier;
use Carbon\Carbon;

/**
 * 新規得意先
 */
class NewCustomerEditController extends Controller
{
    const SCREEN_NAME = 'new-customer-edit';

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
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
        //     throw new NotFoundHttpException();
        // }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $Customer = new Customer();
        $General = new General();
        $Person = new Person();
        $CustomerBranch = new CustomerBranch();
        $customerData = null;
        $categoryData = null;
        $personData = null;
        $branchData = null;
        $checkBoxData = $General->getCategoryList(config('const.general.com'));
        $juridList = $General->getCategoryList(config('const.general.juridical'));
        $LockManage = new LockManage();
        $lockData = null;
        $customerList = (new Customer())->getComboList();
        // 回収サイト取得
        $kbn = config('const.general.paysight');
        $collectSight = $General->getCategoryList($kbn);
        // 回収区分
        $kbn = config('const.general.collection');
        $collectKbn = $General->getCategoryList($kbn);
        // 手数料区分
        $kbn = config('const.general.customerfee');
        $feeKbn = $General->getCategoryList($kbn);
        // 税計算区分
        $kbn = config('const.general.taxcalc');
        $taxCalcKbn = $General->getCategoryList($kbn);
        // 税端数
        $kbn = config('const.general.taxrounding');
        $taxRounding = $General->getCategoryList($kbn);
        // 仕入先リスト取得
        $supKbn[] = config('const.supplierMakerKbn.supplier');
        $supplierList = (new Supplier())->getBySupplierKbn($supKbn);

        $staffList = (new Staff())->getComboList();
        $staffDepartList = (new StaffDepartment())->getCurrentTermList();
        $departmentList = (new Department())->getComboList();

        try {
            if (is_null($id)) {
                // 新規
                $customerData = $Customer;
                Common::initModelProperty($customerData);
                $categoryData = $General;
                $personData = $Person;
                Common::initModelProperty($personData);
                $branchData = $CustomerBranch;
                Common::initModelProperty($branchData);
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $customerData = $Customer->getById($id);
                $categoryData = $Customer->getCompanyCategory($id) ? $Customer->getCompanyCategory($id) : '';
                $personData = $Person->getById($id) ? $Person->getById($id) : $Person;
                $branchData = $CustomerBranch->getById($id) ? $CustomerBranch->getById($id) : $CustomerBranch;

                $customerList = $customerList->whereNotIn('id', $id);
                // $list = array_values($cKeywordList->toArray());  
                
                // foreach($categoryData as $data){
                //     if(empty($data['value_code'])){
                //         $data['value_code'] = 0;
                //     }
                // }


                $path = config('const.uploadPath.customer');
                $files = Storage::files($path);
                if(isset($files)){
                    $plen = strlen($path);
                    foreach($files as $file){
                        $fileInfo = pathinfo($path.$file);
                        $cusIdLen = strlen($customerData['id']);
                        $customerId = substr($file, $plen, $cusIdLen);
                        if((int)$customerId === $customerData['id']){
                            if($fileInfo['extension'] === 'png'){
                                $customerData['customer_image'] = config('const.encode.png').base64_encode(Storage::get($file));
                            }
                            if($fileInfo['extension'] === 'jpeg' || $fileInfo['extension'] === 'jpg'){
                                $customerData['customer_image'] = config('const.encode.jpeg').base64_encode(Storage::get($file));
                            }
                        }
                    }
                }

                foreach($personData as $data) {
                    if(isset($data['image_file']) && $data['image_file'] !== ''){
                        $path = config('const.uploadPath.person').$data['id'].'/'.$data['image_file'];
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
        

        return view('Customer.new-customer-edit') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('customerData', $customerData)
                ->with('categoryData', $categoryData)
                ->with('personData', $personData)
                ->with('branchData', $branchData)
                ->with('checkBoxData', $checkBoxData)
                ->with('juridList', $juridList)
                ->with('customerList', $customerList)
                ->with('collectSight', $collectSight)
                ->with('collectKbn', $collectKbn)
                ->with('feeKbn', $feeKbn)
                ->with('taxCalcKbn', $taxCalcKbn)
                ->with('taxRounding', $taxRounding)
                ->with('supplierList', $supplierList)
                ->with('staffList', $staffList)
                ->with('staffDepartList', $staffDepartList)
                ->with('departmentList', $departmentList)
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
        $rtnList = array('status' => false, 'msg' => '', 'url' => \Session::get('url.intended', url('/dashboard')));

        // 編集権限チェック
        // $hasEditAuth = false;
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            // $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->all();
            // 各リスト取得
            $persons = [];
            $branchs = [];
            if (!empty($params['person'])) {
                $persons = $params['person'];
            }
            if (!empty($params['branch'])) {
                $branchs = $params['branch'];
            }
            // 企業業種の合計
            $sum = '0';
            if($params['company_category'] !== null && $params['company_category'] !== '0'){
                foreach($params['company_category'] as $data){
                    $sum += $data;
                }
            }
            $params['company_category'] = $sum;

            // 得意先画像の有無
            $hasImage = $request->hasFile('image');
            
            if($hasImage){
                $uploadPath = config('const.uploadPath.customer');
                $this->imageValid($request);
            }

            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }

            // バリデーションチェック
            $this->isValid($request);
        // }

        DB::beginTransaction();
        try{
            // if (!$hasEditAuth) {
            //     // 編集権限なし
            //     throw new \Exception();
            // }
            $delLock = false;
            $Customer = new Customer(); 
            $Person = new Person();
            $CustomerBranch = new CustomerBranch();
            $saveResult = false;
            if ($newFlg) {
                // 登録
                $newCustomerId = $Customer->add($params);
                if($hasImage){
                    // 拡張子取得
                    $extension = $request->file('image')->getClientOriginalExtension();
                    // アップロード
                    $request->file('image')->storeAs($uploadPath, $newCustomerId.'.'.$extension);
                }
                // 種別:得意先
                $type = config('const.person_type.customer');
                $uploadPath = config('const.uploadPath.person');
                foreach($persons as $ps){
                    if(!empty($ps['name'])){
                        $newPersonId = $Person->add($ps, $type, $newCustomerId);
                        if(!empty($ps['file'])){
                            $extension = $ps['file']->getClientOriginalExtension();
                            $ps['file']->storeAs($uploadPath.$newPersonId, $newPersonId.'.'.$extension);
                        }
                    }
                }
                foreach($branchs as $br){
                    if(!empty($br['branch_name'])){
                        $CustomerBranch->add($br, $newCustomerId);
                    }
                }

                $saveResult = true;
                $delLock = true;
            } else {
                // 更新
                $customerId = $params['id'];
                $Customer->updateById($params);
                if($hasImage){
                    // 拡張子取得
                    $extension = $request->file('image')->getClientOriginalExtension();
                    // アップロード
                    $request->file('image')->storeAs($uploadPath, $params['id'].'.'.$extension);
                }
                foreach($persons as $ps){
                    $type = config('const.person_type.customer');
                    $uploadPath = config('const.uploadPath.person');
                    
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
                    }else if(!empty($ps['name'])){
                        // 新規で登録されたパーソン
                        if(!empty($ps['file']) && $ps['file'] !== null){
                            $fileName = $ps['file']->getClientOriginalName();
                        }else {
                            $fileName = '';
                        }
                        $ps['id'] = $Person->add($ps, $type, $customerId, $fileName);

                        if(!empty($ps['file']) && $ps['file'] !== null){
                            Storage::deleteDirectory($uploadPath.$ps['id'], $ps['id']);
                            $ps['file']->storeAs($uploadPath.$ps['id'], $fileName);
                        }
                    }
                }
                foreach($branchs as $br){
                    if(!empty($br['id']) && !empty($br['branch_name'])){
                        // 既に登録されている得意先支店
                        if ($br['del_flg'] == config('const.flg.off')) {
                            $CustomerBranch->updateById($br, $customerId);
                        }else {
                            $CustomerBranch->deleteById($br);
                        }
                    }else if(!empty($br['branch_name'])){
                        // 新規で登録された得意先支店
                        $CustomerBranch->add($br, $customerId);
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
            // 編集権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.customer.edit')) === config('const.authority.none')) {
                throw new \Exception();
            }
            $Customer = new Customer();

            // 削除
            $deleteResult = $Customer->deleteById($params['id']);
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
     * 有効化
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function activate(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            // 編集権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.customer.edit')) === config('const.authority.none')) {
                throw new \Exception();
            }
            $Customer = new Customer();

            // 有効化
            $activateResult = $Customer->activateById($params['id']);
            // ロック解放
            $keys = array($params['id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            

            if ($activateResult && $delLock) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.activate'));
            } else {
                throw new \Exception(config('message.error.activate'));
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
            // 得意先
            'customer_name' => 'required|max:50',
            'customer_short_name' => 'required|max:30',
            'customer_kana' => 'required|max:50',
            'corporate_number' => 'max:13',
            'honorific' => 'max:50',
            'tel' => 'max:20',
            'fax' => 'max:20',
            'email' => 'max:100',
            'zipcode' => 'max:7|nullable|regex:/^[0-9]+$/',
            'address1' => 'max:50',
            'address2' => 'max:50',
            'customer_code' => 'max:10',
            'customer_rank' => 'nullable|numeric',
            'closing_day' => 'nullable|numeric',
            'collection_day' => 'nullable|numeric',
            'bill_min_price' => 'nullable|numeric',
            'bill_rate' => 'nullable|numeric',
            'housing_history_login_id' => 'max:50',
            'housing_history_password' => 'max:50',
            // パーソン
            'name.*' => 'max:10',
            'kana.*' => 'max:20',
            'belong_name.*' => 'max:50',
            'position.*' => 'max:50',
            'tel1.*' => 'max:20',
            'tel2.*' => 'max:20',
            'fax.*' => 'max:20',
            'email1.*' => 'max:100',
            'email2.*' => 'max:100',
            // 支店／作業場
            'branch_name.*' => 'max:50',
            'branch_kana.*' => 'max:50',
        ]);
    }

    protected function imageValid(Request $request) 
    {
        $this->validate($request, [
            'image' => 'required|file|image|mimes:jpeg,png,jpg',
        ]);
    }
}

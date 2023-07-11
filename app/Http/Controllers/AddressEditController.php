<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use DB;
use Session;
use Storage;
use Auth;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Address;
use App\Models\Matter;
use Log;

class AddressEditController extends Controller
{


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
     * @param Request $request 遷移前のデータ
     * @param $id 案件ID
     * @return type
     */
    public function index($id = null)
    {
        $Address = new Address();
        $Matter = new Matter();
        $addressData = null;

        $id = (int)$id;
        $matterData = $Matter->getById($id);

        if (is_null($matterData['address_id']) || $matterData['address_id'] == 0) {
            // 新規
            $addressData = $Address;
            $addressData['matter_id'] = $id;
        } else {
            // 編集            
            // データ取得
            $addressData = $Address->getById($matterData['address_id']);
            $addressData['matter_id'] = $id;

            // 画像データ取得
            $path = config('const.uploadPath.address');
            $files = Storage::files($path);
            if(isset($files)){
                $plen = strlen($path);
                foreach($files as $file){
                    $fileInfo = pathinfo($path.$file);
                    $adrIdLen = strlen($addressData['id']);
                    $addressId = substr($file, $plen, $adrIdLen);
                    if((int)$addressId === $addressData['id']){
                        if($fileInfo['extension'] === 'png'){
                            $addressData['image'] = config('const.encode.png').base64_encode(Storage::get($file));
                        }
                        if($fileInfo['extension'] === 'jpeg' || $fileInfo['extension'] === 'jpg'){
                            $addressData['image'] = config('const.encode.jpeg').base64_encode(Storage::get($file));
                        }
                    }
                }            
            }
        }

        return view('Address.address-edit')
            ->with('addressData', $addressData)
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

        // リクエストデータ取得
        $params = $request->request->all();

        $newFlg = false;
        if (empty($params['id'])) {
            $newFlg = true;
        }

        // 画像の有無
        $hasImage = $request->hasFile('image');
            
        if($hasImage){
            $uploadPath = config('const.uploadPath.address');
            $this->imageValid($request);
        }

        // バリデーションチェック
        $this->isValid($request);

        
        DB::beginTransaction();
        $Address = new Address();
        $Matter = new Matter();
        try {
            $saveResult = false;
            if ($newFlg) {
                // 登録
                $newAddressId = $Address->add($params);
                $Matter->updateForAddress($params['matter_id'], $newAddressId);

                if($hasImage){
                    // 拡張子取得
                    $extension = $request->file('image')->getClientOriginalExtension();
                    // アップロード
                    $request->file('image')->storeAs($uploadPath, $newAddressId.'.'.$extension);
                }

                $saveResult = true;
            } else {
                // 更新
                $Address->updateById($params);
             
                if($hasImage){
                    // 拡張子取得
                    $extension = $request->file('image')->getClientOriginalExtension();
                    // 既にアップロードされている画像削除
                    $files = Storage::files($uploadPath);
                    if(isset($files)){
                        $plen = strlen($uploadPath);
                        foreach($files as $file){
                            $adrIdLen = strlen($params['id']);
                            $addressId = substr($file, $plen, $adrIdLen);
                            if($addressId === $params['id']){
                                Storage::delete($file);
                            }
                        }
                    // アップロード
                    $request->file('image')->storeAs($uploadPath, $params['id'].'.'.$extension);
                    }
                }

                $saveResult = true;
            }

            if ($saveResult) {
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
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid(Request $request)
    {
        $this->validate($request, [
            'zipcode' => 'max:7',
            'address1' => 'required|max:50',
            'address2' => 'max:50',
            'latitude_jp' => 'numeric',
            'longitude_jp' => 'numeric',
            'latitude_world' => 'numeric',
            'longitude_world' => 'numeric',
        ]);
    }

    /**
     * 画像ファイル　バリデーションチェック
     *
     * @param Request $request
     * @return void
     */
    protected function imageValid(Request $request) 
    {
        $this->validate($request, [
            'image' => 'required|file|image|mimes:jpeg,png,jpg',
        ]);
    }
}
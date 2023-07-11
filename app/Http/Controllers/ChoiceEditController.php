<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Choice;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Item;
use App\Models\LockManage;
use App\Models\SpecItemDetail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DB;
use Session;
use Storage;
use Carbon\Carbon;

/**
 * 選択肢編集
 */
class ChoiceEditController extends Controller
{
    const SCREEN_NAME = 'choice-edit';

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

        $Choice = new Choice();  
        $isUsed = config('const.flg.off');      

        try {
            if (is_null($id)) {
                // 新規
                $choiceData = $Choice;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $choiceData = $Choice->getById($id);
                $choiceData = $Choice->getByKeyword($choiceData['choice_keyword']);
                
                foreach($choiceData as $key => $val) {
                    // 仕様項目に使われているかチェック
                    $specList = (new SpecItemDetail())->getByKeyword($choiceData[$key]['choice_keyword'])->first();
                    if(!empty($specList)) {
                        $isUsed = config('const.flg.on');
                    }
                }

                // 画像取得
                $path = config('const.uploadPath.item_choice');
                $files = Storage::files($path);
                foreach ($choiceData as $key => $val) {
                    if (isset($files)){
                        foreach($files as $file){
                            $fileInfo = pathinfo($path.$file);
                            if($fileInfo['basename'] === $choiceData[$key]['image_path']){
                                if(in_array($fileInfo['extension'], config('const.extEncode.png'))){
                                    $choiceData[$key]['image'] = config('const.encode.png').base64_encode(Storage::get($file));
                                }
                                if(in_array($fileInfo['extension'], config('const.extEncode.jpeg'))){
                                    $choiceData[$key]['image'] = config('const.encode.jpeg').base64_encode(Storage::get($file));
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }        

        return view('Choice.choice-edit') 
                ->with('isEditable', $isEditable)
                ->with('choiceData', $choiceData)
                ->with('isUsed', $isUsed)
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

            // 選択肢データ
            $choiceList = $params['choiceList'];
            $choiceIDs = collect($choiceList)->KeyBy('id')->pluck('id')->toArray();
            $newFlg = false;
            if (empty($choiceIDs[0])) {
                $newFlg = true;
            }
        }
        $Choice = new Choice();

        // キーワードUniqueチェック
        if ($newFlg) {
            $List = $Choice->getAllData();
            $existKeys = $List->whereIn('choice_keyword', $params['choice_keyword'])->toArray();
            if (count($existKeys) != 0) {
                // 既に存在した場合
                $err = config('message.error.choice.exist_keyword');
                return \Response::json(['error' => $err]);
            }
        } else {
            $List = $Choice->getByNotInIDs($choiceIDs);
            $existKeys = $List->whereIn('choice_keyword', $params['choice_keyword'])->toArray();
            if (count($existKeys) != 0) {
                // 既に存在した場合
                $err = config('message.error.choice.exist_keyword');
                return \Response::json(['error' => $err]);
            }
        }


        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();        
        try {
            if (!$hasEditAuth) {
                // 編集権限なし
                throw new \Exception();
            }
            // ファイル名 yyyyMMddhhiiss_{ID}
            $fileName = date('YmdHis'). '_'. Auth::user()->id;
            
            foreach ($choiceList as $key => $val) {
                $newFlg = false;
                if (empty($choiceList[$key]['id'])) {
                    $newFlg = true;
                }
                $seqNo = sprintf("%02d", (int)$key + 1);

                $uploadPath = config('const.uploadPath.item_choice');
                $saveResult = false;
                if ($newFlg) {  
                    // 登録
                    $file = $request->file($key);
                    if (!empty($file)) {
                        // 画像有
                        $fileExtension = $file->getClientOriginalExtension();
                        $choiceList[$key]['image_path'] = $fileName. '_'. $seqNo. '.'. $fileExtension;
                        $file->storeAs($uploadPath, $fileName. '_'. $seqNo. '.'. $fileExtension);
                    }

                    $choiceId = $Choice->add($choiceList[$key]);
                    $saveResult = true;                    
                } else {
                    // 更新
                    $choiceId = (int)$choiceList[$key]['id'];

                    $file = $request->file($key);
                    if (!empty($file)) {
                        // 画像有
                        $fileExtension = $file->getClientOriginalExtension();  
                        $choiceList[$key]['image_path'] = $fileName. '_'. $seqNo. '.'. $fileExtension;
                        $file->storeAs($uploadPath, $fileName. '_'. $seqNo. '.'. $fileExtension);
                    }

                    $saveResult = $Choice->updateById($choiceList[$key]);
                }

                if (!$saveResult) {
                    throw new \Exception(config('message.error.save'));
                }
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

            $Choice = new Choice();

            foreach ($params as $key => $val) {
                // 削除
                $deleteResult = $Choice->deleteById($val);
            }

            if ($deleteResult) {
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
            'choice_keyword' => 'required|max:20',
        ]);
    }

}
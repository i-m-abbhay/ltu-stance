<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Illuminate\Support\Facades\Log;
use Session;
use Validator;
use App\Models\Authority;
use App\Models\Warehouse;

class LoginController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    protected $table = 'm_staff';
    public $timestamps = false;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('guest')->except('logout');
    // }

    public function index()
    {
        if (Auth::id()) {
            return redirect('/');
        }
        // if(isset($_SERVER['HTTP_REFERER'])){
        //     session(['url.intended' => $_SERVER['HTTP_REFERER']]);
        // }
        return view('/login');
    }

    /**
     * ログイン認証
     *
     * @param Request $request
     * @return void
     */
    public function loginCheck(Request $request)
    {
        $rtnList = array('status' => false, 'msg' => '', 'url' => \Session::get('url.intended', url('/')));
        $user = new User();

        // バリデーションチェック
        $this->isValid($request);

        // リクエストデータ取得
        $params = $request->request->all();

        try {
            if (Auth::attempt(['login_id' => $params['login_id'], 'password' => $params['password'], 'retirement_kbn' => config('const.flg.off'), 'del_flg' => config('const.flg.off')])) {
                $rtnList['status'] = true;
                $user = User::where('login_id', $params['login_id'])->first();
                Auth::login($user);

                $hasAuth = collect([
                    'master' => [
                        'inquiry' => Authority::hasAuthority(Auth::id(), config('const.auth.master.inquiry')),
                        'edit' => Authority::hasAuthority(Auth::id(), config('const.auth.master.edit'))
                    ],
                    'auth' => [
                        'setting' => Authority::hasAuthority(Auth::id(), config('const.auth.authority.setting')),
                    ]
                ]);
                Session::put('hasAuth', $hasAuth);

                //現在値から一番近い倉庫を取得してセッションに保持
                $Warehouse = new Warehouse();
                $data = $Warehouse->getByLocation($params['latitude'], $params['longitude']);
                $warehouse_id = 0;
                if (Count($data) > 0) {
                    $warehouse_id = $data[0]['id'];
                }
                Session::put('warehouse_id', $warehouse_id);
                Session::put('latitude', $params['latitude']);
                Session::put('longitude', $params['longitude']);

                return \Response::json($rtnList);
            } else {
                $rtnList['msg'] = config('message.error.loginErr');
            }
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.loginErr'));
        }
        return \Response::json($rtnList);
    }

    /**
     * ログアウト
     *
     * @return void
     */
    public function logout(Request $request)
    {
        Auth::logout();
        // セッションに持つログイン情報をリセット
        $request->session()->flush();
        $request->session()->put('url.intended', url()->previous());

        return redirect()->route('login');
    }


    /**
     * バリデーションチェック
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    private function isValid(Request $request)
    {
        $this->validate($request, [
            'login_id' => 'required|max:11',
            'password' => 'required',
        ]);
    }
}

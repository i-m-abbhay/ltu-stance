<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\General;
use App\Models\Customer;
use App\Models\NumberManage;
use App\Libs\Common;
use App\Models\ApprovalHeader;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\SystemUtil;

/**
 * ワークフロー
 * TODO:承認の仕組を変えたので一旦未使用。当ファイル削除する場合はWebサーバに残らないように。
 */
class ApprovalController extends Controller
{
}
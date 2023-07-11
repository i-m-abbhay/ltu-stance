<?php

namespace App\Models;

use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;
use DB;

/**
 * 請求
 */
class Requests extends Model
{
    // テーブル名
    protected $table = 't_request';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'request_no',
        'customer_id',
        'customer_name',
        'charge_department_id',
        'charge_department_name',
        'charge_staff_id',
        'charge_staff_name',
        'request_mon',
        'closing_day',
        'request_s_day',
        'request_e_day',
        'be_request_e_day',
        'shipment_at',
        'expecteddeposit_at',
        'lastinvoice_amount',
        'offset_amount',
        'deposit_amount',
        'receivable',
        'different_amount',
        'carryforward_amount',
        'purchase_volume',
        'sales',
        'additional_discount_amount',
        'consumption_tax_amount',
        'discount_amount',
        'total_sales',
        'request_amount',
        'request_day',
        'request_user',
        'request_up_at',
        'request_up_user',
        'sales_category',
        'image_sign',
        'status',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];


    /**
     * IDでデータ取得
     *
     * @param string $id ID
     * @return void
     */
    public function getById($id)
    {
        // Where句作成
        $where = [];
        $where[] = array('id', '=', $id);
        //$where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 新規登録
     *
     * @param $params
     * @return $result
     */
    public function add($params)
    {
        $result = false;

        $now    = Carbon::now();
        $user   = Auth::user()->id;

        try {
            $params['del_flg'] = config('const.flg.off');
            $params['created_user'] = $user;
            $params['created_at'] = $now;
            $params['update_user'] = $user;
            $params['update_at'] = $now;

            $result = $this->insertGetId($params);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 新規登録
     *
     * @param $params
     * @return $result
     */
    public function addToSalesBatch($params)
    {
        $result = false;

        $now    = Carbon::now();
        $user   = config('const.batchUser');

        try {
            $params['del_flg'] = config('const.flg.off');
            $params['created_user'] = $user;
            $params['created_at'] = $now;
            $params['update_user'] = $user;
            $params['update_at'] = $now;

            $result = $this->insertGetId($params);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * 更新
     * @param int $id
     * @param array $params 1行の配列
     * @return void
     */
    public function updateById($id, $params)
    {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $id);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

            // 更新内容
            $items = [];
            $registerCol = $this->getFillable();
            foreach ($registerCol as $colName) {
                if (array_key_exists($colName, $params)) {
                    switch ($colName) {
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user']   = Auth::user()->id;
            $items['update_at']     = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where($where)
                ->update($items);

            $result = ($updateCnt == 1);
            // 登録
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 物理削除
     * @param int $id
     * @return void
     */
    public function deleteById($id)
    {
        $result = 0;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            // 更新
            $result = $this
                ->where('id', $id)
                ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param int $id
     * @return void
     */
    public function softDeleteById($id)
    {
        $result = 0;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            // 更新
            $result = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 請求一覧を取得
     * 
     * @return 
     */
    public function getRequestList($params)
    {
        //共通条件
        $where = "";
        if ($params['customer_id'] != null && $params['customer_id'] != 0) {
            $where = $where . " and c.id = " . $params['customer_id'];
        }
        if ($params['department_id'] != null && $params['department_id'] != 0) {
            $where = $where . " and dep.id = " . $params['department_id'];
        }
        if ($params['staff_id'] != null && $params['staff_id'] != 0) {
            $where = $where . " and s.id = " . $params['staff_id'];
        }
        if ($params['closing_date'] != null) {
            $where = $where . " and r.closing_day = " . $params['closing_date'];
        }

        //未確定以外条件
        $where1 = $where;
        if ($params['request_month'] != null) {
            $where1 = $where1 . " and r.request_mon = " . $params['request_month'];
        }
        if ($params['request_no'] != null && $params['request_no'] != "") {
            $where1 = $where1 . " and r.request_no = '" . $params['request_no'] . "'";
        }
        DB::statement("SET @rownum=0;");
        $data = DB::select("
            select
                 @rownum:=@rownum+1 as id
                 ,0 as chk
                 ,case when r.status = 1 then 'printable' 
                 when r.status in (2,4) then 'issued'
                 when r.status = 3 then 'closed' 
                 when r.status = 0 then 'undecided' 
                 end as status
                 ,r.status as status_code
                 ,r.id as request_id
                 ,r.request_mon as request_mon
                 ,min(dep.department_name) as department_name
                 ,min(s.staff_name) as staff_name
                 ,min(dep.id) as department_id
                 ,min(s.id) as staff_id
                 ,r.request_no
                 ,r.customer_id as customer_id
                 ,CONCAT(COALESCE(min(c_general.value_text_2), \"\"), COALESCE(min(c.customer_name), \"\"), COALESCE(min(c_general.value_text_3), \"\")) as customer_name
                 ,count(distinct sd.matter_id) as matter_count
                 ,DATE_FORMAT(r.request_e_day,'%Y-%m-%d') as closing_day
                 ,r.closing_day as closing_code
                 ,date_format(r.shipment_at,'%Y-%m-%d') as shipment_at
                 ,r.lastinvoice_amount
                 ,r.offset_amount
                 ,r.deposit_amount
                 ,r.carryforward_amount
                 ,r.sales as current_month_sales
                 ,r.additional_discount_amount as discount
                 ,consumption_tax_amount as consumption_tax
                 ,r.discount_amount
                 ,r.sales_category
                 ,r.request_s_day
                 ,r.request_e_day
                 ,r.sales as sales
                 ,consumption_tax_amount as consumption_tax_amount
                 
                 ,total_sales as current_month_sales_total
                  ,request_amount as billing_amount
                  ,date_format(r.request_day,'%Y-%m-%d') as request_day
                  ,r.request_user
                  ,min(s2.staff_name) as request_user_name
                 
            from t_request as r
            left join t_sales as sale on sale.request_id = r.id
            left join t_sales_detail as sd on sale.id = sd.sales_id and r.del_flg = 0
            left join t_matter as m on m.id = sd.matter_id and m.del_flg = 0 
            left join m_customer as c on c.id = r.customer_id and c.del_flg = 0
            left join m_general as c_general on c.juridical_code = c_general.value_code and c_general.category_code = '" .  config('const.general.juridical') . "' 
            left join m_department as dep on dep.id = r.charge_department_id and dep.del_flg = 0
            left join m_staff as s on s.id = r.charge_staff_id and s.del_flg = 0
            left join m_staff as s2 on s2.id = r.request_user and s2.del_flg = 0
            
            where r.del_flg = 0"
            . $where1 .
            " group by r.id,r.customer_id
            ");

        return $data;
    }

    /**
     * 請求状態更新(発行済み解除)
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function cancellationById($id)
    {
        $result = false;
        
        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

        try {
            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'request_day' => null,
                    'request_user' => null,
                    'status' => 1,
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 締め情報設定
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function updateClosingInfo($customer_id, $request_month)
    {
        $result = false;
        $where = [];
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('request_mon', '=', $request_month);
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $statusList = [config('const.requestStatus.val.request_complete'), config('const.requestStatus.val.release')];

        $data = $this
                ->where($where)
                ->whereIn('status', $statusList)
                ->get();

        $LogUtil = new LogUtil();

        try {
            if (count($data) > 0) {
                foreach($data as $key => $row) {
                    // ログテーブルへの書き込み
                    $LogUtil->putById($this->table, $row['id'], config('const.logKbn.update'));
                    
                    $updateCnt = $this
                        ->where('id', $row['id'])
                        ->update([
                            'request_up_at' =>  Carbon::now(),
                            'request_up_user' => Auth::user()->id,
                            'status' => 3,
                            'update_user' => Auth::user()->id,
                            'update_at' => Carbon::now(),
                        ]);
                }
            }
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 請求書発行情報クリア
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function clearInvoice($id)
    {
        $result = false;
        try {
            $updateCnt = $this
                ->where('id', $id)
                ->where('status', 0)
                ->update([
                    'request_day' =>  null,
                    'request_user' => null,
                    'status' => 0,
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 締め解除されているかどうか
     * @param array $params 検索条件の配列
     * @return bool 検索結果データ
     */
    public function isCancel($customer_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('status', '=', 4);


        // データ取得
        $data = $this
            ->where($where)
            ->select(
                '*'
            )
            ->get();

        if ($data == null || count($data) <= 0) {
            $rtn = false;
        } else {
            $rtn = true;
        }

        return $rtn;
    }

    /**
     * 売上確定状態のデータが存在しているかどうか
     * @param array $params 検索条件の配列
     * @return bool 検索結果データ
     */
    public function isExistSalesConfirmed($customer_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('status', '=', 1);


        // データ取得
        $data = $this
            ->where($where)
            ->select(
                '*'
            )
            ->get();

        if ($data == null || count($data) <= 0) {
            $rtn = false;
        } else {
            $rtn = true;
        }

        return $rtn;
    }


    /**
     * 既にデータが存在しないか
     * @param array $params 検索条件の配列
     * @return bool 検索結果データ
     */
    public function getNotClosing($customer_id, $request_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('status', '<>', 3);
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('id', '<>', $request_id);


        // データ取得
        $data = $this
            ->where($where)
            ->select(
                '*'
            )
            ->get();

        return $data;
    }

    /**
     * 既にステータス「0：未処理」以外のデータが存在しないか
     * @param array $params 検索条件の配列
     * @return bool 検索結果データ
     */
    public function isExistsOtherRequest($customer_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('customer_id', '=', $customer_id);


        // データ取得
        $data = $this
            ->where($where)
            ->whereIn('status', [0, 3])
            ->select(
                '*'
            )
            ->get();

        if ($data == null || count($data) > 0) {
            $rtn = true;
        } else {
            $rtn = false;
        }

        return $rtn;
    }

    /**
     * 最新の請求データを取得
     * @param array $params 検索条件の配列
     * @return bool 検索結果データ
     */
    public function getNewData($customer_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('status', '=', 3);


        // データ取得
        $data = $this
            ->where($where)
            ->select(
                '*'
            )
            ->orderBy('request_day', 'desc')
            ->first();

        return $data;
    }

    /**
     * 発行解除不可であるかどうか
     * @param array $params 検索条件の配列
     * @return bool 検索結果データ
     */
    public function isExistsRequestSales($customer_id, $request_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('r.del_flg', '=', config('const.flg.off'));
        $where[] = array('r.customer_id', '=', $customer_id);
        // $where[] = array('r.status', '<>', 3);


        // データ取得
        $data = $this
            ->from('t_request as r')
            ->where($where)
            ->select(
                '*'
            )
            ->orderBy('created_at', 'desc')
            ->first();

        if ($data['id'] == $request_id && ($data['status'] == 2 || $data['status'] == 4)) {
            $rtn = false;
        } else {
            $rtn = true;
        }

        return $rtn;
    }

    /**
     * 締め解除されているかどうか
     * @param array $params 検索条件の配列
     * @return int 請求月
     */
    public function isLastClosed($customer_id)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('status', '=', 3);


        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('request_mon', 'desc')
            ->select(
                'request_mon'
            )
            ->get();

        return count($data) > 0 ? $data[0]['request_mon'] : null;
    }


    /**
     * 締め解除を行う請求の一覧取得
     * 売上期間終了日の昇順で返す(最新は最終行)
     *
     * @param [type] $customer_id
     * @return void
     */
    public function getClosedRequestData($customer_id, $request_mon) 
    {
        $where = [];
        $where[] = array('customer_id', '=', $customer_id);
        $where[] = array('request_mon', '=', $request_mon);
        $where[] = array('status', '=', config('const.requestStatus.val.close'));
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->orderBy('request_e_day', 'asc')
            ->get();

        return $data;
    }

    /**
     * 締め解除
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function closingCancel($customer_id)
    {
        $result = false;
        $LogUtil = new LogUtil();

        $data = $this
            ->where('customer_id', $customer_id)
            ->where('status', 3)
            ->where('del_flg', config('const.flg.off'))
            ->whereRaw('request_mon = (select * from (select max(request_mon) from t_request as r where r.del_flg = 0 and r.customer_id = ' . $customer_id . ' and r.status = 3) as t)')
            ->get();

        try {
            if (count($data) > 0) {
                foreach($data as $row) {
                    // ログテーブルへの書き込み
                    $LogUtil->putById($this->table, $row['id'], config('const.logKbn.update'));

                    $updateCnt = $this
                        ->where('id', $row['id'])
                        ->update([
                            'request_up_at' => null,
                            'request_up_user' => null,
                            'status' => 4,
                            'update_user' => Auth::user()->id,
                            'update_at' => Carbon::now(),
                        ]);
                }
            }
            
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 請求一覧を取得（いきなり売上）
     * 
     * @return 
     */
    public function getNewRequestOfCounterSale($params)
    {
        $where = [];
        $where[] = array('c.id', '=', $params['customer_id']);
        $where[] = array('c.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('m_customer as c')

            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })

            ->leftJoin('m_department as d', function ($join) {
                $join->on('d.id', '=', 'c.charge_department_id')
                    ->where('d.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_staff as s', function ($join) {
                $join->on('c.charge_staff_id', '=', 's.id')
                    ->where('s.del_flg', '=', config('const.flg.off'));
            })
            ->where($where)
            ->selectRaw("
            c.id as customer_id,
            CONCAT(COALESCE(c_general.value_text_2, \"\"), COALESCE(c.customer_name, \"\"), COALESCE(c_general.value_text_3, \"\")) as customer_name,
            c.charge_department_id,
            d.department_name as charge_department_name,
            c.charge_staff_id,
            s.staff_name as charge_staff_name,
            ifnull((select request_mon from t_request where customer_id = c.id and status <> 2 order by request_mon desc limit 1),
                     case when (select request_mon from t_request where customer_id = c.id and status = 2 order by request_mon desc limit 1)
                                = case when date_format(CURDATE(),'%d') > c.closing_day then date_format(date_add(CURDATE(),interval 1 month),'%Y%m') else date_format(CURDATE(),'%Y%m') end		  
                                  then date_format(date_add(case when date_format(CURDATE(),'%d') > c.closing_day then date_add(CURDATE(),interval 1 month) else CURDATE() end ,interval 1 month),'%Y%m')
                                  else date_format(case when date_format(CURDATE(),'%d') > c.closing_day then date_add(CURDATE(),interval 1 month) else CURDATE() end ,'%Y%m') end ) as request_mon
                ")
            ->first();

        return $data;
    }

    /**
     * 新規登録(いきなり売上)
     *
     * @param $params
     * @return $result
     */
    public function addCounterSale($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'request_no' => $params['request_no'],
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_department_name' => $params['charge_department_name'],
                'charge_staff_id' => $params['charge_staff_id'],
                'charge_staff_name' => $params['charge_staff_name'],
                'request_mon' => $params['request_mon'],
                'purchase_volume' => $params['purchase_volume'],
                'sales' => $params['sales'],
                'consumption_tax_amount' => $params['consumption_tax_amount'],
                'total_sales' => $params['total_sales'],
                'request_amount' => $params['request_amount'],
                'image_sign' => $params['image_sign'],
                'request_day' => Carbon::now(),
                'shipment_at' => Carbon::now(),
                'expecteddeposit_at' => Carbon::now(),
                'request_user' => Auth::user()->id,
                'sales_category' => $params['sales_category'],
                'status' => $params['status'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 指定した得意先の最新の請求データを取得する
     * @param $customerId
     */
    public function getLatestData($customerId)
    {
        $where = [];
        $where[] = array('t_request.customer_id', '=', $customerId);
        $where[] = array('t_request.del_flg', '=', config('const.flg.off'));

        $subQuery = DB::table('t_request')
            ->select([
                DB::raw('MAX(request_e_day) as request_e_day')
            ])
            ->where($where);

        $data = $this
            ->join(DB::raw('(' . $subQuery->toSql() . ') as sub1'), function ($join) {
                $join->on('t_request.request_e_day', 'sub1.request_e_day');
            })
            ->mergeBindings($subQuery)
            ->select([
                't_request.*',
                DB::raw('DATE_FORMAT(t_request.request_s_day, "%Y/%m/%d") as request_s_day'),
                DB::raw('DATE_FORMAT(t_request.request_e_day, "%Y/%m/%d") as request_e_day'),
                DB::raw('DATE_FORMAT(t_request.be_request_e_day, "%Y/%m/%d") as be_request_e_day'),
                DB::raw('DATE_FORMAT(t_request.shipment_at, "%Y/%m/%d") as shipment_at'),
                DB::raw('DATE_FORMAT(t_request.expecteddeposit_at, "%Y/%m/%d") as expecteddeposit_at'),
                DB::raw('DATE_FORMAT(t_request.update_at, "%Y/%m/%d") as update_at'),
                DB::raw('t_request.update_at as update_at_raw'),
            ])
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 指定した得意先の最新の請求データを取得する
     * 「未処理」「確定済み」を除く
     * @param $customerId
     */
    public function getLatestCompleteData($customerId)
    {
        $where = [];
        $where[] = array('t_request.customer_id', '=', $customerId);
        $where[] = array('t_request.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_request.status', '!=', config('const.requestStatus.val.unprocessed'));
        $where[] = array('t_request.status', '!=', config('const.requestStatus.val.complete'));

        $subQuery = DB::table('t_request')
            ->select([
                DB::raw('MAX(request_e_day) as request_e_day')
            ])
            ->where($where);

        $data = $this
            ->join(DB::raw('(' . $subQuery->toSql() . ') as sub1'), function ($join) {
                $join->on('t_request.request_e_day', 'sub1.request_e_day');
            })
            ->mergeBindings($subQuery)
            ->select([
                't_request.*',
            ])
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 指定した得意先と開始日のデータを取得する
     * @param $customerId   得意先ID
     * @param $requestId    除外する請求ID
     * @param $requestSDay  売上開始日
     */
    public function isExistByRequestSDay($customerId, $requestId, $requestSDay)
    {
        $result = false;

        $where = [];
        $where[] = array('t_request.customer_id', '=', $customerId);
        $where[] = array('t_request.id', '!=', $requestId);
        $where[] = array('t_request.request_s_day', '=', $requestSDay);
        $where[] = array('t_request.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->select([
                't_request.id',
            ])
            ->where($where)
            ->first();
        if ($data !== null) {
            $result = true;
        }

        return $result;
    }

    /**
     * 請求を締めていない計上月を取得する
     * ステータスが「請求書作成済」のデータを取得する
     * @param $customerId
     */
    public function getNotColoseRequestMon($customerId)
    {
        $where = [];
        $where[] = array('t_request.customer_id', '=', $customerId);
        $where[] = array('t_request.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->select([
                't_request.request_mon',
            ])
            ->where($where)
            ->whereIn('t_request.status', [config('const.requestStatus.val.request_complete'), config('const.requestStatus.val.release')])
            ->first();

        return $data;
    }

    /**
     * 締め月の売上金額取得
     * 
     * @return 
     */
    public function getSalesAmount($params)
    {
        $where = [];
        $where[] = array('request_mon', '=', $params['request_mon']);
        $where[] = array('customer_id', '=', $params['customer_id']);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->groupBy('customer_id')
            ->selectRaw("
                            sum(sales) as sales,
                            sum(deposit_amount) as deposit_amount,
                            sum(offset_amount) as offset_amount
                            
                        ")
            ->first();

        return $data;
    }

    /**
     * 請求月取得（請求一覧）
     * 
     * @return 
     */
    public function getRequestMonthForRequestList($params)
    {
        $where = [];
        $where[] = array('customer_id', '=', $params['customer_id']);
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $data = $this
            ->where($where)
            ->whereIn('status', [2, 4])
            ->selectRaw('request_mon')
            ->first();

        if ($data != null) {
            return $data['request_mon'];
        } else {
            return null;
        }
    }

    /**
     * 請求書発行状態更新
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function updateInvoiceStatus($params)
    {
        $result = false;
        try {
            $where = [];
            $where[] = array('customer_id', '=', $params['customer_id']);
            $where[] = array('status', '=', $params['status']);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

            if (array_key_exists('request_no', $params)) {
                $this
                    ->where('customer_id', $params['customer_id'])
                    ->where('status', $params['status'])
                    ->update([
                        'request_no' => $params['request_no'],
                        'request_day' => $params['request_day'],
                        'status' => 2,
                        'request_user' => Auth::user()->id,
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            } else {
                $this
                    ->where('customer_id', $params['customer_id'])
                    ->where('status', $params['status'])
                    ->update([
                        'request_day' => $params['request_day'],
                        'status' => 2,
                        'request_user' => Auth::user()->id,
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            }


            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 入金データ作成用一覧を取得
     * 
     * @return 
     */
    public function getCreditedList($requestId)
    {
        $where = [];
        $where[] = array('r.id', '=', $requestId);
        $where[] = array('r.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_request as r')
            ->leftJoin('m_customer as c', function ($join) {
                $join->on('c.id', '=', 'r.customer_id')
                    ->where('c.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })

            ->leftJoin('m_department as d', function ($join) {
                $join->on('d.id', '=', 'c.charge_department_id')
                    ->where('d.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_staff as s', function ($join) {
                $join->on('c.charge_staff_id', '=', 's.id')
                    ->where('s.del_flg', '=', config('const.flg.off'));
            })

            ->where($where)
            ->selectRaw("
                    r.id as request_id
                    ,r.request_no
                    ,r.customer_id
                    ,CONCAT(COALESCE(c_general.value_text_2, \"\"), COALESCE(c.customer_name, \"\"), COALESCE(c_general.value_text_3, \"\")) as customer_name
                    ,r.charge_department_id
                    ,r.charge_department_name
                    ,r.charge_staff_id
                    ,r.charge_staff_name
                    ,r.closing_day as customer_closing_day
                    ,r.expecteddeposit_at
                    ,c.closing_day
                    ,c.collection_sight
                    ,c.collection_day
                    ,c.collection_kbn
                    ,c.bill_min_price
                    ,c.bill_rate
                    ,c.fee_kbn
                    ,c.tax_calc_kbn
                    ,c.tax_rounding
                    ,c.offset_supplier_id
                    ,c.bill_sight
                    ,r.receivable
                    ,r.different_amount
                    ,r.carryforward_amount
                    ,r.total_sales
                    ,r.request_amount
            ")
            ->first();

        return $data;
    }

    /**
     * 請求書所情報を取得
     * 
     * @return 
     */
    public function getInvoiceData($params)
    {
        $where = [];
        $where[] = array('r.id', '=', $params['request_id']);
        $where[] = array('r.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_request as r')

            ->leftJoin('m_customer as c', function ($join) {
                $join->on('c.id', '=', 'r.customer_id');
            })
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })

            ->leftJoin('m_department as d', function ($join) {
                $join->on('d.id', '=', 'r.charge_department_id')
                    ->where('d.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_base as base', function ($join) {
                $join->on('d.base_id', '=', 'base.id');
            })
            ->leftJoin('m_staff as s', function ($join) {
                $join->on('r.charge_staff_id', '=', 's.id')
                    ->where('s.del_flg', '=', config('const.flg.off'));
            })

            ->leftJoin('m_own_bank as o1', function ($join) {
                $join->on('o1.id', '=', 'd.own_bank_id_1')
                    ->where('o1.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_bank as b1', function ($join) {
                $join->on('b1.bank_code', '=', 'o1.bank_code')
                    ->on('b1.branch_code', '=', 'o1.branch_code')
                    ->where('b1.del_flg', '=', config('const.flg.off'));
            })

            ->leftJoin('m_own_bank as o2', function ($join) {
                $join->on('o2.id', '=', 'd.own_bank_id_2')
                    ->where('o2.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_bank as b2', function ($join) {
                $join->on('b2.bank_code', '=', 'o2.bank_code')
                    ->on('b2.branch_code', '=', 'o2.branch_code')
                    ->where('b2.del_flg', '=', config('const.flg.off'));
            })
            ->where($where)
            ->whereIn("r.status", [0, 1, 2, 3, 4])
            ->selectRaw("
                        r.id as request_id
                        ,r.request_e_day as request_day
                        ,r.request_day as print_day
                        ,r.request_s_day
                        ,r.request_e_day
                        ,r.request_no
                        ,r.customer_id
                        ,r.charge_staff_id
                        ,c.zipcode
                        ,c.address1
                        ,c.address2
                        ,c.customer_name
                        ,CONCAT(COALESCE(c_general.value_text_2, \"\"), COALESCE(c.customer_name, \"\"), COALESCE(c_general.value_text_3, \"\")) as customer_name
                        ,c.customer_code
                        ,r.lastinvoice_amount as carryforward_amount
                        ,r.deposit_amount
                        ,r.offset_amount
                        ,r.discount_amount                        
                        ,r.sales
                        ,r.consumption_tax_amount
                        ,r.carryforward_amount as next_carryforward_amount
                        ,d.department_name
                        ,d.tel as department_tel
                        ,d.fax as department_fax
                        ,s.staff_name
                        ,base.zipcode as base_zipcode
                        ,base.address1 as base_address1
                        ,base.address2 as base_address2
                        ,o1.account_type as account_type1
                        ,o1.account_number as account_number1
                        ,o1.account_name as account_name1
                        ,b1.bank_name as bank_name1
                        ,b1.branch_name as branch_name1

                        ,o2.account_type as account_type2
                        ,o2.account_number as account_number2
                        ,o2.account_name as account_name2
                        ,b2.bank_name as bank_name2
                        ,b2.branch_name as branch_name2
            ")
            ->first();

        return $data;
    }
    /**
     * 売上額取得
     *
     * @param $staff_id      担当者ID
     * @param $ym            年月(yyyymm)
     * @return void
     */
    public function getPreviousYearSalesByStaffId($staff_id, $start_ym, $end_ym)
    {
        $where = [];
        $where[] = array('req.charge_staff_id', '=', $staff_id);
        $whereBetween = [];
        $whereBetween[] = array($start_ym, $end_ym);

        $data = $this
            ->from('t_request AS req')
            ->where($where)
            ->whereBetween('req.request_mon', $whereBetween)
            ->selectRaw('
                    SUM(req.total_sales) AS total_sales
                ')
            ->get();

        return $data;
    }

    /**
     * 売上額取得
     *
     * @param $customer_id   得意先ID
     * @param $ym            年月(yyyymm)
     * @return void
     */
    public function getLastMonthSales($customer_id, $ym)
    {
        $where = [];
        $where[] = array('req.customer_id', '=', $customer_id);
        $where[] = array('req.request_mon', '=', $ym);

        $data = $this
            ->from('t_request AS req')
            ->where($where)
            ->selectRaw('
                    req.purchase_volume,
                    req.sales,
                    req.additional_discount_amount
                ')
            ->get();

        return $data;
    }

    /**
     * 売上合計を期間で取得
     *
     * @param $customer_id  得意先ID
     * @param $start_ym     開始日(yyyymm)
     * @param $end_ym       終了日(yyyymm)
     * @return void
     */
    public function getSalesByYearMonth($customer_id, $start_ym, $end_ym)
    {
        $where = [];
        $where[] = array('req.customer_id', '=', $customer_id);
        $whereBetween = [];
        $whereBetween[] = array($start_ym, $end_ym);

        $data = $this
            ->from('t_request AS req')
            ->where($where)
            ->whereBetween('req.request_mon', $whereBetween)
            ->selectRaw('
                    SUM(req.purchase_volume) AS total_purchase_volume,
                    SUM(req.sales) AS total_sales,
                    SUM(req.additional_discount_amount) AS total_additional_discount_amount
                ')
            ->get();

        return $data;
    }


    /**
     * 請求書番号リスト取得
     *
     * @return void
     */
    public function getRequestNoList()
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->select([
                'request_no'
            ])
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 指定得意先の入金されていない請求額を取得(売掛金)
     * 一部入金されていても繰越済み以外は入金としては扱わない
     * 請求が未処理状態のデータを除く
     * 入金が違算有 or 未入金は入金データがあっても入金としては扱わない
     * @param $customerId  得意先ID
     * @return void
     */
    public function getCustomerTotalSales($customerId)
    {
        $result = 0;

        $SystemUtil = new SystemUtil();
        $tmpRequestInfo = $SystemUtil->getStrictCurrentMonthRequestPeriod($customerId);
        $requestEDay = "'" . $tmpRequestInfo['request_e_day'] . "'";

        $where = [];
        $where[] = array('t_request.customer_id', '=', $customerId);
        // $where[] = array('t_request.status', '!=', config('const.requestStatus.val.unprocessed'));
        $where[] = array('t_request.del_flg', '=', config('const.flg.off'));
        $statusList = [config('const.requestStatus.val.unprocessed'), config('const.requestStatus.val.complete')];

        $subWhere = [];
        $subWhere[] = array('t_request.customer_id', '=', $customerId);
        $subWhere[] = array('t_request.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('c.del_flg', '=', config('const.flg.off'));

        // 入金処理日の最新を取得
        $newestCreditedQuery = DB::table('t_credited AS c')
                ->leftJoin('t_request', 't_request.id', '=', 'c.request_id')
                ->leftJoin('t_credited_detail AS cd', function($join) {
                    $join
                        ->on('c.id', '=', 'cd.credited_id')
                        ->where('cd.del_flg', '=', config('const.flg.off'));
                })
                ->where($subWhere)
                ->whereNotIn('t_request.status', $statusList)
                // ->where(function ($query) {
                //     $query->orWhere('c.status', config('const.creditedStatus.val.transferred'));
                //     $query->orWhere('c.status', config('const.creditedStatus.val.payment'));
                //     $query->orWhereNull('c.id');
                // })
                ->select([
                    'c.id AS credited_id',
                    DB::raw('MAX(cd.credited_date) AS credited_date'),
                ])
                ->groupBy('c.id')
                // ->get()
                ;

        // 該当得意先の請求金額を合計して取得
        $data = $this
            ->leftJoin('t_credited', function ($join) {
                $join->on('t_request.id', '=', 't_credited.request_id')
                    ->where('t_credited.del_flg', '=', config('const.flg.off'));
            })
            ->join(DB::raw('(' . $newestCreditedQuery->toSql() . ') as t_credited_detail'), function ($join) {
                $join
                    ->on('t_credited_detail.credited_id', '=', 't_credited.id');
                    // ->whereRaw('t_credited_detail.credited_date > '. $requestEDay);
            })
            // ->leftJoin(DB::raw('('. $newestCreditedQuery->toSql() .') AS t_credited_detail'), 't_credited_detail.credited_id', '=', 't_credited.id')
            ->mergeBindings($newestCreditedQuery)
            ->where($where)
            ->whereNotIn('t_request.status', $statusList)
            ->where(function ($query) use($requestEDay) {
                $query->whereRaw('t_credited_detail.credited_date > '. $requestEDay);
                $query->orWhere('t_credited.status', config('const.creditedStatus.val.unsettled'));
                $query->orWhere('t_credited.status', config('const.creditedStatus.val.miscalculation'));
                // $query->orWhereNull('t_credited_detail.credited_date');
            })
            // ->where(function ($query)  {
            //     $query->orWhere('t_credited.status', config('const.creditedStatus.val.unsettled'));
            //     $query->orWhere('t_credited.status', config('const.creditedStatus.val.miscalculation'));
            //     $query->orWhereNull('t_credited.id');
            // })
            ->select([
                DB::raw("SUM(t_request.total_sales) AS total_sales"),
            ])
            ->first();

        if ($data !== null) {
            $result = intval(Common::nullorBlankToZero($data->total_sales));
        }

        return $result;
    }

    /**
     * 翌月分更新
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function updateNextMonth($params)
    {
        $result = false;
        try {

            $this
                ->where('id', $params['id'])
                ->update([
                    'request_no' => $params['request_no'],
                    'customer_id' => $params['customer_id'],
                    'customer_name' => $params['customer_name'],
                    'charge_department_id' => $params['charge_department_id'],
                    'charge_department_name' => $params['charge_department_name'],
                    'charge_staff_id' => $params['charge_staff_id'],
                    'charge_staff_name' => $params['charge_staff_name'],
                    'request_mon' => $params['request_mon'],
                    'closing_day' => $params['closing_day'],
                    'request_s_day' => $params['request_s_day'],
                    'request_e_day' => $params['request_e_day'],
                    'be_request_e_day' => $params['be_request_e_day'],
                    'shipment_at' => $params['shipment_at'],
                    'expecteddeposit_at' => $params['expecteddeposit_at'],
                    'lastinvoice_amount' => $params['lastinvoice_amount'],
                    'offset_amount' => $params['offset_amount'],
                    'deposit_amount' => $params['deposit_amount'],
                    'receivable' => $params['receivable'],
                    'different_amount' => $params['different_amount'],
                    'carryforward_amount' => $params['carryforward_amount'],
                    'purchase_volume' => $params['purchase_volume'],
                    'sales' => $params['sales'],
                    'additional_discount_amount' => $params['additional_discount_amount'],
                    'consumption_tax_amount' => $params['consumption_tax_amount'],
                    'discount_amount' => $params['discount_amount'],
                    'total_sales' => $params['total_sales'],
                    'request_amount' => $params['request_amount'],
                    'request_day' => $params['request_day'],
                    'request_user' => $params['request_user'],
                    'request_up_at' => $params['request_up_at'],
                    'request_up_user' => $params['request_up_user'],
                    'sales_category' => $params['sales_category'],
                    'image_sign' => $params['image_sign'],
                    'status' => $params['status'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);

            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
    /**
     * 新規登録(翌月分))
     *
     * @param $params
     * @return $result
     */
    public function addNextMonth($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'request_no' => $params['request_no'],
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_department_name' => $params['charge_department_name'],
                'charge_staff_id' => $params['charge_staff_id'],
                'charge_staff_name' => $params['charge_staff_name'],
                'request_mon' => $params['request_mon'],
                'closing_day' => $params['closing_day'],
                'request_s_day' => $params['request_s_day'],
                'request_e_day' => $params['request_e_day'],
                'be_request_e_day' => $params['be_request_e_day'],
                'shipment_at' => $params['shipment_at'],
                'expecteddeposit_at' => $params['expecteddeposit_at'],
                'lastinvoice_amount' => $params['lastinvoice_amount'],
                'offset_amount' => $params['offset_amount'],
                'deposit_amount' => $params['deposit_amount'],
                'receivable' => $params['receivable'],
                'different_amount' => $params['different_amount'],
                'carryforward_amount' => $params['carryforward_amount'],
                'purchase_volume' => $params['purchase_volume'],
                'sales' => $params['sales'],
                'additional_discount_amount' => $params['additional_discount_amount'],
                'consumption_tax_amount' => $params['consumption_tax_amount'],
                'discount_amount' => $params['discount_amount'],
                'total_sales' => $params['total_sales'],
                'request_amount' => $params['request_amount'],
                'request_day' => $params['request_day'],
                'request_user' => $params['request_user'],
                'request_up_at' => $params['request_up_at'],
                'request_up_user' => $params['request_up_user'],
                'sales_category' => $params['sales_category'],
                'image_sign' => $params['image_sign'],
                'status' => $params['status'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 得意先IDで取得
     *
     * @param [type] $customerId
     * @return void
     */
    public function getByCustomerId($customerId)
    {
        $data = $this
            ->where([
                ['del_flg', config('const.flg.off')],
                ['customer_id', $customerId],
            ])
            ->get();
        return $data;
    }

    /**
     * 請求書発行前更新
     * @param  $id 
     * @return boolean True:成功 False:失敗 
     */
    public function updateInvoice($params)
    {

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));
        $result = false;
        try {
            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'deposit_amount' =>  $params['deposit_amount'],
                    'receivable' =>  $params['receivable'],
                    'carryforward_amount' =>  $params['carryforward_amount'],
                    'request_amount' =>  $params['request_amount'],
                    'offset_amount' => $params['offset_amount'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 請求データの売上期間が最新の1件を取得する
     *
     * @param [type] $customerId
     * @return void
     */
    public function getLatestSalesPeriodRequests($customerId)
    {
        $data = $this
                    ->where([
                        ['del_flg', '=', config('const.flg.off')],
                        ['customer_id', '=', $customerId]
                    ])
                    ->orderBy('request_e_day', 'desc')
                    ->first();
        return $data;
    }

    /**
     * 案件詳細での案件完了チェック用
     * 　締め済以外の請求に紐づく売上明細データの存在確認をする
     *
     * @param int $customerId 得意先ID
     * @param int $matterId 案件ID
     * @return boolean true:存在しない false:存在する
     */
    public function isValidCompleteMatter($customerId, $matterId)
    {
        $count = $this
                    ->join('t_sales_detail', function($join){
                        $join
                            ->on('t_request.id', '=', 't_sales_detail.request_id')
                            ->where('t_sales_detail.del_flg', '=', config('const.flg.off'));
                    })
                    ->where([
                        ['t_request.del_flg', '=', config('const.flg.off')],
                        ['t_request.customer_id', '=', $customerId],
                        ['t_sales_detail.matter_id', '=', $matterId],
                    ])
                    ->whereIn('t_request.status', [
                        config('const.requestStatus.val.unprocessed'),
                        config('const.requestStatus.val.complete'),
                        config('const.requestStatus.val.request_complete'),
                        config('const.requestStatus.val.release'),
                        config('const.requestStatus.val.temporary_delete'),
                    ])
                    ->count();
        return ($count == 0);
    }

    /**
     * ダッシュボード 売上情報
     * 売上額取得
     *
     * @return void
     */
    public function getSalesAmountByBetweenDate($params)
    {
        // Where句
        $where = [];
        $where[] = array('r.del_flg', '=', config('const.flg.off'));        
        if (!empty($params['customer_id'])) {
            $where[] = array('r.customer_id', '=', $params['customer_id']);
        }
        if (!empty($params['staff_id'])) {
            $where[] = array('s.matter_staff_id', '=', $params['staff_id']);
        }
        if (!empty($params['department_id'])) {
            $where[] = array('s.matter_department_id', '=', $params['department_id']);
        }

        // 売上Where
        $salesWhere = [];
        $salesWhere[] = array('s.del_flg', '=', config('const.flg.off'));

        // 売上明細Where
        $salesDetailWhere = [];
        $salesDetailWhere[] = array('sd.del_flg', '=', config('const.flg.off'));
        $salesDetailWhere[] = array('sd.notdelivery_flg', '<>', config('const.notdeliveryFlg.val.invalid'));

        if(!empty($params['start_date'])) {
            $salesDetailWhere[] = array('sd.sales_date', '>=', $params['start_date']);
        }
        if(!empty($params['end_date'])) {
            $salesDetailWhere[] = array('sd.sales_date', '<=', $params['end_date']);
        }

        $data = DB::table('t_request AS r')
                ->leftJoin('t_sales AS s', function($join) use($salesWhere) {
                    $join
                        ->on('s.request_id', '=', 'r.id')
                        ->where($salesWhere)
                        ;
                })
                ->leftJoin('t_sales_detail AS sd', function($join) use($salesDetailWhere) {
                    $join
                        ->on('sd.sales_id', '=', 's.id')
                        ->where($salesDetailWhere)
                        ;
                })
                ->where($where)
                ->select([
                    DB::raw('SUM(sd.sales_total) AS sales_total')
                ])
                ->first()
                ;

        return $data;
    }


    /**
     * 一時削除された請求を取得する
     * 削除フラグ[1]　状態フラグ[5]
     *
     * @param string $id ID
     * @return void
     */
    public function getTemporaryDeleteRequestData($customerId)
    {
        // Where句作成
        $where = [];
        $where[] = array('customer_id', '=', $customerId);
        $where[] = array('del_flg', '=', config('const.flg.on'));
        $where[] = array('status', '=', config('const.requestStatus.val.temporary_delete'));

        // データ取得
        $data = $this
            ->where($where)
            ->first();

        return $data;
    }
}

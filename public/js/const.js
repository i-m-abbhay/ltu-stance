/*** メッセージ定義 ***/
var MSG_ERROR = 'エラーが発生しました。';
var MSG_EDITING = '他ユーザーが編集中です。';
var MSG_CONFIRM_SAVE = '保存します。よろしいですか？';
var MSG_CONFIRM_DELETE = '削除します。よろしいですか？';
var MSG_CONFIRM_INITIALIZE = '初期化します。よろしいですか？';
var MSG_CONFIRM_ACTIVATE = '有効化します。よろしいですか？';
var MSG_CONFIRM_UNLOCK = '他ユーザーのロックを強制解除して編集モードにします。よろしいですか？';
var MSG_CONFIRM_LOCK_RELEASE = 'ロックを解除しますか？変更は保存されません';
var MSG_CONFIRM_LEAVE = 'ロックしたままこのページから離れますか？';
var MSG_CONFIRM_LEAVE_EDITED = '編集した内容は保存されていませんがよろしいですか？';
var MSG_CONFIRM_APPLY = '承認申請します。よろしいですか？';
var MSG_CONFIRM_APPLY_CANCEL = '承認申請を取消します。よろしいですか？';
var MSG_CONFIRM_RECIVED_ORDER = '受注確定します。よろしいですか？';
var MSG_CONFIRM_CANCEL_RECIVED_ORDER = '受注確定を取り消します。よろしいですか？';
var MSG_CONFIRM_QUOTE_COMPLETE = '見積を完了します。よろしいですか？';
var MSG_CONFIRM_QUOTE_DELETE = '見積を削除します。よろしいですか？';
var MSG_CONFIRM_PROCESS_CANCEL = '処置を取り消します。よろしいですか？';
var MSG_CONFIRM_FIX_PURCHASE = '選択した仕入詳細を確定します。よろしいですか？';
var MSG_CONFIRM_CANCEL_PURCHASE = '選択した仕入詳細を解除します。よろしいですか？';
var MSG_CONFIRM_CANCEL_FIX_DETAIL = '選択した仕入詳細を削除します。よろしいですか？';
var MSG_CONFIRM_NOT_CREATE_NOTDELIVERY_DATA = '階層販売金額を利用する明細に「未納」が作成されていません。よろしいですか？';
var MSG_CONFIRM_SALES_RELEASE = '売上確定を解除します。よろしいですか？';
var MSG_CONFIRM_SALES_DELETE = '売上期間を変更します。明細に設定した売上日や請求情報がリセットされます。よろしいですか？';
var MSG_CONFIRM_SALES_STATUS_APPLY = '承認します。よろしいですか？';
var MSG_CONFIRM_SALES_STATUS_SENDBACK = '否認します。よろしいですか？';
var MSG_CONFIRM_PAYMENT_CONFIRM = '支払予定を確定します。よろしいですか？';
var MSG_CONFIRM_PAYMENT_REQUEST_CANCEL = '選択した支払予定の申請を取り消します。よろしいですか？\n※申請はすべて取消されます。';
var MSG_CONFIRM_PAYMENT_REQUEST = '承認します。よろしいですか？';
var MSG_CONFIRM_PAYMENT_CLOSING = '選択している支払予定を締めます。よろしいですか？\n※状況が「支払済」の支払予定が対象となります。';
var MSG_CONFIRM_PRODUCT_USED_BY_QUOTE = '使用中の見積が存在します。入荷できなくなりますがよろしいですか？';
var MSG_CONFIRM_PRODUCT_USED_BY_STOCK = '在庫データが存在します。削除してよろしいですか？';
var MSG_ERROR_CHECKBOX_NO_SELECT = 'チェックボックスを選択してください。';
var MSG_ERROR_MATTER_NO_OR_MATTER_NAME_NO_SELECT = '案件番号 または 案件名を選択してください。';
var MSG_ERROR_UNMATCHED_PRODUCT_MAKER_SELECT = '異なるメーカーの商品が選択されています。';
var MSG_ERROR_UNMATCHED_PRODUCT_SUPPLIER_SELECT = '発注している商品の仕入先は変更できません。';
var MSG_ERROR_ORDER_MAKER_NO_SELECT= '商品マスタへ登録する商品のメーカーが選択されていません。';
var MSG_ERROR_NO_INPUT_ORDER_QUANTITY = '発注数が未入力の商品があります。';
var MSG_ERROR_ORDER_COMPLETE_MAKER_CHANGE = '既に発注している商品のため、異なるメーカーの商品には変更できません。';
var MSG_ERROR_NO_INPUT_SHIPMENT_QUANTITY = '出荷登録数を入力してください。';
var MSG_ERROR_NO_DATA = 'データがありません。';
var MSG_ERROR_FIX_PURCHASE = '確定している明細が選択されています。';
var MSG_ERROR_CANCEL_PURCHASE = '支払が確定済の仕入明細が選択されていました。支払が確定済みの仕入明細は仕入確定は解除されていません。';
var MSG_ERROR_CANCEL_PURCHASE_NOT_FIXED = '仕入確定がされていない明細が含まれています。';
var MSG_ERROR_DELETE_PURCHASE_ADD_ROW = '調整明細を選択してください。';
var MSG_ERROR_PAYMENT_DATE_DIFFERENCE = '選択している支払予定の中に、支払日が異なる支払予定が存在します。確認してください。';
var MSG_ERROR_REQUESTED_PAYMENT = '選択している支払予定に、申請済が存在します。';
var MSG_ERROR_NOT_REQUESTED = '選択している支払予定に、未申請が存在します。';
var MSG_ERROR_REQUEST_PAYMENT_APPROVAL_AUTH = '承認権限のない申請があります。';
var MSG_ERROR_PAYMENT_CLOSING = '支払締めをする権限がありません。';
var MSG_ERROR_OFFSET_AMOUNT_OVER = '売掛相殺金額が、売上金額を超えています。内容を確認してください。';
var MSG_ERROR_NO_INPUT_BILLS_DATA = '手形情報が入力されていません。設定してください。';
var MSG_ERROR_SAME_PRODUCT_ID = '異なる商品を選択してください。';

var MSG_CONFIRM_PRODUCT_CSV_IMPORT = '取込を実行します。よろしいですか？';
var MSG_SUCCESS_PRODUCT_CSV_IMPORT = '取込が完了しました。';

var MSG_ERROR_BACK_TO_PRODUCT = '商品が存在しません。';

var MSG_LOADING = 'Loading...'

var MSG_ERROR_EXIST_QREQ = '見積依頼がすでに存在する案件です。';
var MSF_ERROR_EXIST_QUOTE = '見積がすでに存在する案件です。';
var MSG_ERROR_NO_SELECT = '選択してください。';
var MSG_ERROR_NO_INPUT = '入力してください。';
var MSG_ERROR_NOT_NUMBER = '数値を入力してください。';
var MSG_ERROR_NOT_SAVED = '先に保存を行って下さい。';
var MSG_ERROR_LIMIT_OVER = '文字以内で入力してください。';
var MSG_ERROR_INVALID_RATE = '0.01～99.99の間で入力してください。';
var MSG_ERROR_ANY_NO_INPUT = '未入力のデータがあります。';
var MSG_ERROR_SAME_NICKNAME = '異なる呼び名を設定してください。';
var MSG_ERROR_SAME_PRIORITY = '異なる優先度を設定してください。';
var MSG_ERROR_SAME_INPUT = '異なる値を設定してください。';
var MSG_ERROR_SAME_WAREHOUSE = '異なる倉庫を選択してください。';
var MSG_ERROR_SAME_CHECK_PRODUCT = '既に選択されている商品です。';
var MSG_ERROR_PRODUCT_CODE_USED = '既に存在する商品です。';
var MSG_ERROR_NEXT_DATE = '今日以降の日付を入力してください。';
var MSG_ERROR_LOWER_NUMBER = '以上の数値を入力してください。';
var MSG_ERROR_LIMIT_QUANTITY = '以下の数値を入力してください。';
var MSG_ERROR_QUANTITY_NOT_DIVIDED = 'ロット数を割り切れる値を入力してください。';
var MSG_ERROR_ACTIVE_STOCK_OVER = '有効在庫数以下の値を入力してください。';
var MSG_ERROR_EMPTY_MOVE_INPUT = '出庫数を入力してください。';
var MSG_ERROR_STOCK_OVER = '在庫数以下の値を入力してください。';
var MSG_ERROR_MIN_RESERVE_VALIDITY = '有効な引当数を下回っているため、更新できません。';
var MSG_ERROR_RESERVE_LIMIT_OVER = '引当数以下の値を入力してください。';
var MSG_ERROR_STOCK_QUANTITY_MULTIPLE = '管理数単位で入力してください。';
var MSG_ERROR_CLOSING_DATE_RANGE = '0～28、または99で入力してください。'
var MSG_ERROR_SHOW_DIALOG_REBATE = '「リベート等内訳」の金額が0円です。変更することができません。';
var MSG_ERROR_SHOW_DIALOG_BILLS = '「手形金額」が0円です。変更することができません。'
var MSG_ERROR_NOT_PURCHASE_STAFF = '操作権限がありません。';
var MSG_ERROR_NOT_PURCHASE_AUTH_BILLS = '手形情報を更新する権限がありません。';
var MSG_ERROR_ACCOUNT_OUTPUTTED = '既に、会計データが出力済みです。更新できません。';
var MSG_ERROR_PURCHASE_DIFFERENCE = 'リベートの合計金額と、変更した金額に差異があります。再度確認してください。';
var MSG_ERROR_CONFRIM_PURCHASE_AUTH = '確定する権限がありません。';
var MSG_ERROR_RECEIVABLE_AMOUNT_OVER = '売掛相殺金額が、売上金額を超えています。内容を確認してください。';
var MSG_ERROR_NOT_FIXED = '未確定の支払予定が存在するため、申請できません。';
var MSG_ERROR_SUM_AMOUNTS_DIFFERENCE = '請求金額と支払金額の合計が一致していません。内容を確認してください。';
var MSG_ERROR_PURCHASE_CANCEL = '選択されている支払予定は解除できません。再度確認してください。';
var MSG_ERROR_BILLS_DIFFERENCE = '手形合計と一致していません。';
var MSG_ERROR_BILLS_OUTPUTTED = '「手形出力」が完了済みです。登録・更新できません。';
var MSG_ERROR_OVER_RATE = '割合の合計が「100」になるように入力してください。';
var MSG_ERROR_NOT_UNSETTLED = '確定済みの明細が選択されています。';
var MSG_ERROR_PAST_PRICE_DATE = '改定日が過去日のため、登録できません。';
var MSG_ERROR_START_DATE_CALC_DAYS = '基準日が工程の場合は、0以上の日数を指定してください。';
var MSG_ERROR_PAYMENT_DATE_OVER = '支払予定日を過ぎています。仕入確定処理を続行してもよろしいですか？';
var MSG_ERROR_PURCHASE_PERIOD = '仕入開始日と仕入終了日が正しく設定されていません。確認してください。';
var MSG_ERROR_NO_INPUT_PRODUCT_CODE = '商品番号を設定してください。';

var MSG_START_DATE_OVER = '適用を開始しているため更新できません。';
var MSG_USED_CHOICE = '仕様項目で使用されているため更新できません。';

var MSG_CONFIRM_RETURN_REJECTION = '返品を却下します。よろしいですか？'
var MSG_CONFIRM_UPDATE_REBATE = '「リベート等内訳」の内容を更新します。よろしいですか？';
var MSG_CONFIRM_UPDATE_BILLS = '「手形情報」を更新します。よろしいですか？';
var MSG_CONFIRM_TRANSITION_PURCHASE = '仕入明細画面に遷移します。未確定の情報はクリアされます。よろしいですか？';
var MSG_CONFIRM_PURCHASE_CANCEL = '支払確定を解除します。よろしいですか？';
var MSG_CONFIRM_ARRIVAL_CANCEL = '入荷を取り消します。よろしいですか？';

var MSG_CONFIRM_SAVE_CUSTOMER_STANDARD = '得意先標準登録をします。よろしいですか？';

var MSG_ERROR_NOT_IMAGE = '該当商品の写真はありませんでした。';

var MSG_ERROR_ADD_ROW = '行挿入できません。';
var MSG_ERROR_DELETE_ROW = '行削除できません。';
var MSG_ERROR_CREATE_LAYER = '階層作成できません。';
var MSG_ERROR_ADD_KBN_CREATE_LAYER = '追加部材では階層作成できません。';
var MSG_ERROR_SET_PRODUCT_CREATE_LAYER = '子部品は階層作成できません。';
var MSG_ERROR_LAYER_NAME_NO_INPUT = '階層名は必須です。';
var MSG_ERROR_PASTE = '貼り付けできません。';
var MSG_ERROR_PASTE_FROM_DETAIL_TO_LAYER = '明細から階層への貼り付けはできません。';
var MSG_ERROR_PASTE_TO_SET_PRODUCT = '一式への貼り付けはできません。';
var MSG_ERROR_PASTE_FORMAT = '貼り付けができないフォーマットです。';
var MSG_ERROR_MOVE_TO_OTHER_LAYER = '別の階層への移動はできません。';
var MSG_ERROR_MOVE_TO_CONSTRUCTION_LAYER = '工事区分への移動はできません。';
var MSG_ERROR_MOVE_TO_ADD_CONSTRUCTION_LAYER = '追加部材への移動はできません。';
var MSG_ERROR_MOVE_FROM_ADD_CONSTRUCTION_LAYER = '追加部材は移動できません。';
var MSG_ERROR_MOVE_TO_SET_PRODUCT = '一式商品への移動はできません。';
var MSG_ERROR_NOT_ORDER_LOT_QUANTITY_MULTIPLE = '発注ロットの倍数で入力して下さい。';
var MSG_ERROR_CHANGE_PRODUCT_DUE_TO_MIN_QUANTITY_DIFFERENT = '最小単位数が違うため商品を変更できません。';
var MSG_ERROR_START_DATE = '適用開始日になっていません。';
var MSG_ERROR_NOT_EXISTS_FILE = 'ファイルが存在しません。';
var MSG_ERROR_ILLEGAL_FILE_EXTENSION = 'ファイルの形式が不正です。';
var MSG_ERROR_ILLEGAL_VALUE = '不正な値が存在します。';
var MSG_ERROR_SELECT_TOP_TREE = 'トップ階層ではできません';
var MSG_ERROR_ITEM_DUPLICATE = '項目が重複しています';
var MSG_ERROR_ITEM_SHORTAGE = '項目が不足しています';
var MSG_WARNING_END_DATE = '適用期間を過ぎています。';
var MSG_WARNING_NEW_PRODUCT = '新品番が存在する商品です。';
var MSG_ERROR_CREATE_SET_PRODUCT = '一式作成できません。';
var MSG_ERROR_CREATE_SET_PRODUCT_CONSTRUCTION_LAYER = '工事区分階層は一式作成できません。';
var MSG_ERROR_CREATE_SET_PRODUCT_EXISTS_LAYER = '階層が含まれているため一式作成できません。';
var MSG_ERROR_CREATE_SET_PRODUCT_EXISTS_SET_PRODUCT = '一式が含まれているため一式作成できません。';
var MSG_ERROR_CREATE_SET_PRODUCT_INSIDE_SET_PRODUCT = '子部品は一式作成できません。';
var MSG_ERROR_CREATE_SET_PRODUCT_INSIDE_ADD_CONSTRUCTION_LAYER = '追加部材では一式作成できません。';
var MSG_ERROR_NOT_PRODUCT_MASTER_DATA_ORDER = '商品マスタにない商品が含まれているため発注できません。';
var MSG_ERROR_SET_PRODUCT_ORDER = '一式商品の数量は1つまでです。';
var MSG_ERROR_HINBANNASHI = '無形品として扱っているため、品番無しボタンを使用できません。';
var MSG_ERROR_FROM_TANGIBLE_TO_INTANGIBLE = '有形品として扱っているため、無形品に変更できません。';
var MSG_ERROR_FROM_INTANGIBLE_TO_TANGIBLE = '無形品として扱っているため、有形品に変更できません。';
var MSG_ERROR_REQUIRED_TANGIBLE_PRODUCT_CODE = '有形品の商品コードは必須入力です。';
var MSG_ERROR_CHANGE_INTANGIBLE_PRODUCT_CODE = '無形品の商品コードの変更はできません。空白にするか無形品を選択してください。';
var MSG_ERROR_INTANGIBLE_ORDER = '無形品が含まれているため発注できません。';
var MSG_ALERT_TO_INTANGIBLE = '無形品への変更のため最小単位が「$min_quantity」に変更されます。';
var MSG_ERR_GRID_SELECTED_ROW = '選択範囲が不正です。行ヘッダーをクリックして選択してください。';
var MSG_ERROR_ADD_SALES_ROW = '選択された階層の上位に、販売金額利用の階層が含まれています。作成する階層を再度検討してください。';
var MSG_ERROR_SALES_DATE_NO_INPUT = '売上日は必須入力です。';
var MSG_ERROR_SALES_DATE_SALES_PERIOD_THAN_ALSO_BEFORE = '売上日を売上期間よりも前に設定できません。';
var MSG_ERROR_SALES_PERIOD_MULTIPLE_CREATE_NOTDELIVERY = '売上期間内に複数の「未納」は作成できません';
var MSG_ERROR_OUTSIDE_SALES_PERIOD_CREATE_DATA = '「納品」「返品」以外は売上日を売上期間外に設定できません。';
var MSG_ERROR_OUTSIDE_SALES_PERIOD_NOT_DELIVERY_DATA = '「未納」は、売上期間を超えて設定することができません。';
var MSG_ERROR_OUTSIDE_SALES_PERIOD_OFFSET_DATA = '相殺された明細の売上日は売上期間外に設定できません。';
var MSG_ERROR_DELIVERY_OUTSIDE_SALES_PERIOD = '「未納」を作成した場合、「納品」の売上日を売上期間外に設定できません。';
var MSG_ERROR_CUSTOMER_NAME_NO_INPUT = '顧客名は必須入力です。';
var MSG_ERROR_BE_SALES_DATE_PERIOD_OUTSIDE = '指定できない日付です。';
var MSG_ERROR_NOT_CLOSED_SALES_DATE = '前月の請求の締めが完了していません。確認の上再度実行してください。';
var MSG_ERROR_SUDDEN_DELIVERY_DATE = '出荷日が未来日のため登録できません。';

var MSG_ERROR_MULTIPLE_SELECTION = '複数選択されています。';
var MSG_ERROR_NO_SELECT_SET_PRODUCT = '一式商品を選択してください。';
var MSG_ERROR_FILE_RESELECTION = 'ファイルを再選択してください。';
var MSG_ERROR_ORDER_ARRIVAL_PLAN_DATE = '入荷予定日は明日以降の日付を入力して下さい。';
var MSG_ERROR_NO_SELECT_PRODUCT = '入力または選択してください。';

var MSG_ERROR_MAKERID_DIFFERENT = 'メーカーIDが不一致の商品には統合できません。';
var MSG_ERROR_MIN_QUANTITY_DIFFERENT = '最小単位数が不一致の商品には統合できません。';

var MSG_ERROR_NO_ITEM_CNT = '項目がなくなるため削除できません。';

var MSG_ERROR_REQUIRED = '必須です';

var MSG_SUCCESS_SAVE_CUSTOMER_DEFAULT = '工務店標準登録しました。';

var MSG_CONFIRM_MATTER_COMPLETE = '案件完了しますか？\n完了した案件は、見積修正・売上確定が行えなくなり、残っている在庫引当は引当解除されます。';
var MSG_CONFIRM_MATTER_CANCEL_COMPLETE = '案件完了を解除しますか？';
var MSG_ERROR_MATTER_GANTT_STANDARD_DATE = '上棟日が着工日を超えるように設定してください。'
var MSG_ERROR_INPUT_GREATER_THAN_ZERO = '0より大きい値を入力してください';
var MSG_ERROR_EXISTS_RECEIVED_ORDER = '受注確定データが存在します。'

var LBL_FILE = 'ファイルを選択してください';

/*** フォーマット定義 ***/
var FORMAT_DATE = 'YYYY/MM/DD';
var FORMAT_DATETIME = 'YYYY/MM/DD HH:mm:ss';


/*** 定数定義 ***/
// モード用パラメータ
var QUERY_MODE = 'mode';
// 直接編集モード
var MODE_EDIT = 2;

// 編集可/不可
var FLG_NOT_EDITABLE = 0
var FLG_EDITABLE = 1

// 見積依頼入力　必ず存在するタブ
var QUOTE_REQ_ATTACH_TAB = { construction_id: 99999999999, construction_name: '添付' };
// 見積版タブ用パラメータ名
var QUOTE_VERSION_TAB_PARAM = 'tabversion=';

// 可変列幅の最小値
var GRID_COL_MIN_WIDTH = 20;

// 商品コードで有効な値 & 不正な値を入力された際のエラーメッセージ
var PRODUCT_CODE_REGEX = '^[0-9a-zA-Z\\/\\.#&\\*,\\$\\+\\-=\\(\\)\\[\\]_@\\? ]*$';
var MSG_ERROR_PRODUCT_CODE_REGEX = '【使用可能文字】\n・半角英数字\n・半角スペース\n・記号（ / . # & * , $ + - = () [] _ @ ? ）';

// 商品のオートコンプリートの設定値
var PRODUCT_AUTO_COMPLETE_SETTING = {
    SEARCH_PRODUCT_CODE_LENGTH: 3,
    SEARCH_PRODUCT_NAME_LENGTH: 3,
    MAX_LIST_COUNT: 1000,
    DEFAULT_PRODTCT_ID: -1,
    PRODUCT_CODE_URL: '/common/get-product-code-combo-list',
    PRODUCT_CODE_INCLUDE_AUTO_FLG_URL: '/common/get-product-code-combo-list-include-auto-flg',
    PRODUCT_CODE_ALL_URL: '/common/get-product-code-combo-list-all',
    PRODUCT_NAME_URL: '/common/get-product-name-combo-list',
    PRODUCT_NAME_INCLUDE_AUTO_FLG_URL: '/common/get-product-name-combo-list-include-auto-flg',


    // 商品コードのオートコンプリートの上限数超過時に表示する値
    OVER_PRODUCT_CODE_LIST : {
        product_id: -1,
        product_code: '検索結果が多いため表示できません',
        product_name: '',
        maker_id: 0,
        min_quantity: 1,
        order_lot_quantity: 1,
        intangible_flg: 0,
    },
    // 商品名のオートコンプリートの上限数超過時に表示する値
    OVER_PRODUCT_NAME_LIST : {
        product_id: -1,
        product_code: '',
        product_name: '検索結果が多いため表示できません',
        product_short_name: '',
        maker_id: 0,
        min_quantity: 1,
        order_lot_quantity: 1,
        intangible_flg: 0,
    }
};
// 木材立米単価　水増し率
var WOOD_UNIT_PRICE_RATE = {
    // 仕入単価
    PURCHASE_RATE: 0.94,
    // 販売単価
    SALES_RATE: 0.7,
};

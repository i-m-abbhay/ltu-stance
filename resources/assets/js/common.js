/***** 共通 *****/
import { BigNumber } from 'bignumber.js';

export default {

    data: () => ({
        processing: false,
        FLG_ON: 1,
        FLG_OFF: 0,
        CALC_RATE_MULTI_PLIER: 100,     // 小数第2位までの数値を整数にする
        CALC_RATE_DENOMINATOR: 10000,   // 割合を求めるときの分母
        REG_EXP_KEY_WORD: /[\\^$.*+?()[\]{}|]/g,
    }),
    methods: {
        // undefined・nullを排除して空文字を返す
        rmUndefinedBlank: function (str) {
            return (str !== undefined && str !== null) ? str : ''
        },
        // undefined・空文字・nullを排除して0を返す
        rmUndefinedZero: function (num) {
            return (num !== undefined && num !== '' && num !== null) ? num : 0
        },
        // Infinity・NaNを排除して0を返す
        rmInvalidNumZero(num){
            return (isFinite(num) === true && !isNaN(num)) ? num : 0
        },
        // 仕入単価・定価など小数点以下を四捨五入する(10.3456⇒10)
        roundDecimalStandardPrice(price){
            var tmp = price;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(price);
            }
            return this.rmInvalidNumZero(BigNumber(tmp).integerValue().toNumber());
        },
        // 販売単価など小数点以下を切り上げる(10.3456⇒11 10.0001⇒11)
        roundDecimalSalesPrice(price){
            var tmp = price;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(price);
            }
            return this.rmInvalidNumZero(BigNumber(tmp).integerValue(BigNumber.ROUND_UP).toNumber());
        },
        // 掛率など小数点第3位を四捨五入して第2位まで返す(10.3456⇒10.35)
        roundDecimalRate(rate){
            var tmp = rate;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(rate);
            }
            return this.rmInvalidNumZero(BigNumber(tmp).decimalPlaces(2).toNumber());
        },
        /**
         * 足し算
         * ブランクは0として計算する
         * @param {*} n1 
         * @param {*} n2 
         * @param {*} isRaw     BigNumber型で返す
         */
        bigNumberPlus(n1, n2, isRaw){
            isRaw = isRaw === undefined ? false : isRaw;
            var tmp = this._cnvBigNumberNum(n1, n2);
            var res = tmp.tmp1.plus(tmp.tmp2);
            return isRaw ? res : res.toNumber();
        },
        /**
         * 引き算
         * ブランクは0として計算する
         * @param {*} n1 
         * @param {*} n2 
         * @param {*} isRaw     BigNumber型で返す
         */
        bigNumberMinus(n1, n2, isRaw){
            isRaw = isRaw === undefined ? false : isRaw;
            var tmp = this._cnvBigNumberNum(n1, n2);
            var res = tmp.tmp1.minus(tmp.tmp2);
            return isRaw ? res : res.toNumber();
        },
        /**
         * 掛け算
         * ブランクは0として計算する
         * @param {*} n1 
         * @param {*} n2 
         * @param {*} isRaw     BigNumber型で返す
         */
        bigNumberTimes(n1, n2, isRaw){
            isRaw = isRaw === undefined ? false : isRaw;
            var tmp = this._cnvBigNumberNum(n1, n2);
            var res = tmp.tmp1.times(tmp.tmp2);
            return isRaw ? res : res.toNumber();
        },
        /**
         * 割り算
         * ブランクは0として計算する
         * @param {*} n1        被除数
         * @param {*} n2        除数
         * @param {*} isRaw     BigNumber型で返す
         */
        bigNumberDiv(n1, n2, isRaw){
            isRaw = isRaw === undefined ? false : isRaw;
            var tmp = this._cnvBigNumberNum(n1, n2);
            var res = tmp.tmp1.div(tmp.tmp2);
            return isRaw ? res : res.toNumber();
        },
        /**
         * 余り
         * ブランクは0として計算する
         * @param {*} n1 
         * @param {*} n2 
         * @param {*} isRaw     BigNumber型で返す
         */
        bigNumberMod(n1, n2, isRaw){
            isRaw = isRaw === undefined ? false : isRaw;
            var tmp = this._cnvBigNumberNum(n1, n2);
            var res = tmp.tmp1.mod(tmp.tmp2);
            return isRaw ? res : res.toNumber();
        },
        /**
         * ブランクとnullを0にして返す
         * 第一引数をBigNumber型にして返す
         * @param {*} n1 
         * @param {*} n2 
         */
        _cnvBigNumberNum(n1, n2){
            var res ={
                tmp1: n1,
                tmp2: n2,
            };
            if(!BigNumber.isBigNumber(res.tmp1)){
                res.tmp1 = BigNumber(this.rmUndefinedZero(res.tmp1));
            }
            if(!BigNumber.isBigNumber(res.tmp2)){
                res.tmp2 = this.rmUndefinedZero(res.tmp2);
            }
            return res;
        },
        /**
         * BigNumber型にして返す
         * @param {*} n1 
         */
        toBigNumber(n1){
            var res = n1;
            if(!BigNumber.isBigNumber(n1)){
                res = BigNumber(this.rmUndefinedZero(n1));
            }
            return res;
        },
        /**
         * ===
         * @param {*} n1 
         * @param {*} n2 
         */
        bigNumberEq(n1, n2){
            var tmp = n1;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(n1);
            }
            return tmp.eq(n2);
        },
        /**
         * >
         * @param {*} n1 
         * @param {*} n2 
         */
        bigNumberGt(n1, n2){
            var tmp = n1;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(n1);
            }
            return tmp.gt(n2);
        },
        /**
         * >=
         * @param {*} n1 
         * @param {*} n2 
         */
        bigNumberGte(n1, n2){
            var tmp = n1;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(n1);
            }
            return tmp.gte(n2);
        },
        /**
         * <
         * @param {*} n1 
         * @param {*} n2 
         */
        bigNumberLt(n1, n2){
            var tmp = n1;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(n1);
            }
            return tmp.lt(n2);
        },
        /**
         * <=
         * @param {*} n1 
         * @param {*} n2 
         */
        bigNumberLte(n1, n2){
            var tmp = n1;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(n1);
            }
            return tmp.lte(n2);
        },

        // 検索条件をセット
        setSearchParams(query, searchParams) {
            query = query.substring(1)
            var tmpArr = query.split('&');
            for (var i = 0; i < tmpArr.length; i++) {
                var item = tmpArr[i].split('=');
                if (item.length == 2) {
                    var srcParam = searchParams;
                    Object.keys(srcParam).forEach(function(key) {
                        if (key == item[0]) {
                            if (item[0].length >= 3 && item[0].lastIndexOf('_id') >= 0 
                                && item[1].length > 0 && isFinite(item[1])) {
                                // パラメータ名の末尾が「_id」で、数値の場合数値キャスト
                                srcParam[key] = parseInt(decodeURIComponent(item[1]));
                            } else {
                                srcParam[key] = decodeURIComponent(item[1]);
                            }
                        }
                    })
                }
            }
        },
        /**
         * 初期検索条件セット用（初期じゃなくても連想配列渡せば使える）
         * @param {*} searchParams 
         * @param {*} initParams 
         */
        setInitSearchParams(searchParams, initParams) {
            Object.keys(searchParams).forEach(function (key) {
                // キーが存在するなら値をセット
                if (key in initParams) {
                    searchParams[key] = initParams[key];
                }
            });
        },
        // 編集モードで開くか判定
        isEditMode(query, isReadOnly, isEditable) {
            var mode = 0;
            if (query.length > 1) {
                query = query.substring(1)
                var tmpArr = query.split('&');
                for (var i = 0; i < tmpArr.length; i++) {
                    var item = tmpArr[i].split('=');
                    if (item.length == 2 && item[0] == QUERY_MODE) {
                        mode = item[1];
                        break;
                    }
                }
            }
            if (isReadOnly != false && isEditable == FLG_EDITABLE && mode == MODE_EDIT) {
                return true;
            } else {
                return false;
            }
        },
        // エラー表示
        showErrMsg(resErr, err) {
            // エラーを配列に格納
            var errList = [];
            var errItems = resErr;
            Object.keys(errItems).forEach(function(key) {
                errList[key] = '';
                errItems[key].forEach((val) => {
                    if (errList[key] != '') {
                        errList[key] += "\n";
                    }
                    errList[key] += val;
                })
            })
            // エラーメッセージ表示
            var errs = err;
            Object.keys(errs).forEach(function(key) {
                if (errList[key]) {
                    errs[key] = errList[key];
                }
            })
        },
        // エラー表示初期化（エラー表示を消す）
        initErr(error) {
            var errs = error;
            Object.keys(errs).forEach(function(key) {
                errs[key] = '';
            })
        },
        // エラー表示初期化（エラー表示を消す...配列対応）
        initErrArrObj(error) {
            var errs = error;
            Object.keys(errs).forEach(function(key) {
                if (Array.isArray(errs[key])) {
                    // 配列
                    errs[key] = [];
                }else if(typeof errs[key] === 'object'){
                    // オブジェクト　※配列もtrueになるので事前にパターンを潰しておく
                    this.initErr(errs[key]);
                }else{
                    // その他
                    errs[key] = '';
                }
            }.bind(this))
        },
        // LocationSearchからURLのみ取得（urlがない場合はブランクが返る）
        getLocationUrl(query){
            var rtnUrl = '';
            var pattern = 'url='
            query = query.substring(1)
            query = query.split(pattern)
            if (query.length >= 2) {
                var listUrl = query[1].split('&')
                rtnUrl = listUrl[0]
            }

            return rtnUrl
        },
        // AutoCompleteに手動で入力した値を選択させる
        setAutoCompleteValue(sender){
            var result = false;
            var text = sender.text;
            if(sender.itemsSource !== null && sender.itemsSource !== undefined){
                var searchMemberPathList = sender.searchMemberPath.split(',');
                var newLine = sender.itemsSource.filter(function(item, index){
                    var res = false;
                    for(let i in searchMemberPathList){
                        if (item[searchMemberPathList[i].trim()] === text){
                            res = true;
                            break;
                        }
                    }
                    return res;
                });
                if(newLine.length === 1){
                    if(sender.selectedIndex === -1){
                        sender.selectedIndex = 0;
                    }
                    sender.selectedItem = newLine[0];
                    result = true;
                }
            }
            return result;
        },
        // 処理中
        isProcessing(){
            return this.processing;
        },
        // 処理開始
        startProcessing() {
            this.processing = true
        },
        // 処理終了
        endProcessing() {
            this.processing = false
        },
        //ファイル名から拡張子を取得する関数
        getExt(filename, withDot=false){
            var pos = filename.lastIndexOf('.');
            if (pos === -1) return '';
            return (withDot) ? filename.slice(pos):filename.slice(pos + 1);
        },
        /**
         * Excelの内容を多次元連想配列にして返す
         * @param {*} worksheet Workbook.sheets[i] ※wijmo.xlsx - Workbook - sheets
         * @param {*} startRow 開始行(3行目にヘッダがあるなら3)
         * @param {*} lowerEndBaseColumnNumer 下端判定基準となる項目名("品番"又は"商品名"の値が存在する行までが取込対象なら[品番, 商品名])
         */
        convertExcelSheetToArray(worksheet, startRow, columnsOfLowerEnd=[]){
            var result = [];

            var startRowIdx = startRow-1;
            var endRowIdx = worksheet.rows.length-1;

            // ワークシートの最終行 > 取込開始行
            if (worksheet.rows.length > startRow) {
                // 基準となる列に値が入力されている部分までを対象範囲とする
                if (columnsOfLowerEnd.length > 0) {
                    var columnsOfLowerEndIdx = [];
                    columnsOfLowerEnd.forEach(rec => {
                        var findIdx = worksheet.rows[startRowIdx].cells.findIndex(cell => {
                            return (cell.value == rec)
                        });
                        if (findIdx !== -1) {
                            columnsOfLowerEndIdx.push(findIdx);
                        }                    
                    });
                }

                // ヘッダ行の配列を作成[品番, 商品名, etc...]
                var columnHeaders = [];
                for (let i = 0; i < worksheet.rows[startRowIdx].cells.length; i++) {
                    const item = worksheet.rows[startRowIdx].cells[i].value;
                    if (this.rmUndefinedBlank(item).trim() != "") {
                        columnHeaders[i] = item;
                    }
                }

                // 下記のような連想配列を作成する
                // [ ['品番' => '', '商品名' => '', etc...], ['品番' => '', '商品名' => '', etc...] ]
                startRowIdx++;  // ヘッダはいらない為+1
                for (var i=startRowIdx; i < (endRowIdx+1); i++) {
                    // EXCELの内容によってはrowsの連番が飛ぶので、その時点でbreak
                    if (!worksheet.rows[i]) {
                        break;
                    }

                    const rowItem = worksheet.rows[i].cells;
                    var isEnd = (columnsOfLowerEndIdx.length > 0) ? true:false;
                    for (const key in columnsOfLowerEndIdx) {
                        if (this.rmUndefinedBlank(String(rowItem[columnsOfLowerEndIdx[key]].value)).trim() != "" ) {
                            isEnd = false;
                        }
                    }
                    if(isEnd) { break }

                    var record = [];
                    for (const key in columnHeaders) {
                        const jpnKey = columnHeaders[key];
                        // セルの情報が存在しない場合が稀にあるので、先に空白をセット
                        record[jpnKey] = "";
                        if (rowItem[key]) {
                            record[jpnKey] = rowItem[key].value;   
                        }
                    }
                    result.push(record);
                }   
            }
            return result;
        },
        /**
         * CSVのデータ
         * @param {*} csvData CSVデータ
         */
        convertCsvToArray(csvData){
            var result = [];

            var tmp = csvData.split("\n"); // 改行を区切り文字として行を要素とした配列を生成

            // 各行ごとにカンマで区切った文字列を要素とした二次元配列を生成
            for(var i=0;i<tmp.length;++i){
                // 最終行でブランクのみの行は終了させる
                if (i === tmp.length-1 && tmp[i] == "") {
                    break;
                }
                // 二次元配列作成
                var rowData = tmp[i].split(',');
                result.push(rowData);
            }
            return result;
        },
        /**
         * 全角⇒半角(英数字)
         * @param {*} str
         */
        zenkakuToHankaku(str) {
            if (this.rmUndefinedBlank(str) == "") { return str; }
            return str.replace(/[Ａ-Ｚａ-ｚ０-９ ．]/g, function(s) {
                return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
            });                
        },
        /**
         * isMatchPattern
         * @param {*} pattern パターン（正規表現）
         * @param {*} str 検査対象
         */
        isMatchPattern(pattern, str){
            var result = false;
            var regexp = new RegExp(pattern, 'g');
            if (regexp.test(str)) {
                result = true;
            }
            return result;
        },
        /**
         * 文字列の日付を秒に変換
         * @param str   文字列 or Dateオブジェクト
         */
        strToTime(str){
            var result = null;
            if(this.rmUndefinedBlank(str) !== ''){
                if(typeof str === 'string'){
                    result = new Date(str).getTime();
                }else if(typeof str === 'number'){
                    result = str;
                }else{
                    result = str.getTime();
                }
            }
            return result;
        },
        /**
         * RegExpで使用する文字列をエスケープする
         * @param {*} string 
         */
        escapeRegExp(string){
            var reHasRegExp = new RegExp(this.REG_EXP_KEY_WORD.source);
            var res =  string && reHasRegExp.test(string)
            ? string.replace(this.REG_EXP_KEY_WORD, '\\$&')
            : string;
            return res;
        },

        /**
         * 【商品コード】既に取得しているオートコンプリートの選択肢から絞り込む
         * @param {*} autoComplete 
         * @param {*} text 
         */
        getProductCodeAutoCompleteFilterData(autoComplete, text){
            // サーバから取得している場合は再取得せずソース内のアイテムから検索する
            var sourceCollection = autoComplete.temporarySourceCollection;
            var queryItems = [];
            text = this.escapeRegExp(text);
            for (let i = 0; i < sourceCollection.length; i++) {
                if (this.isMatchPattern('^(?=.*' + text + ').*$', sourceCollection[i].product_code)) {
                    queryItems.push(sourceCollection[i]);
                }
            }
            return queryItems;
        },

        /**
         * 【商品名】既に取得しているオートコンプリートの選択肢から絞り込む
         * 商品名
         * 商品名略称
         * @param {*} autoComplete 
         * @param {*} text 
         */
        getProductNameAutoCompleteFilterData(autoComplete, text){
            // サーバから取得している場合は再取得せずソース内のアイテムから検索する
            var sourceCollection = autoComplete.temporarySourceCollection;
            var queryItems = [];
            text = this.escapeRegExp(text);
            for (let i = 0; i < sourceCollection.length; i++) {
                if (this.isMatchPattern('^(?=.*' + text + ').*$', sourceCollection[i].product_name)) {
                    queryItems.push(sourceCollection[i]);
                }else if (this.isMatchPattern('^(?=.*' + text + ').*$', sourceCollection[i].product_short_name)) {
                    queryItems.push(sourceCollection[i]);
                }
            }
            return queryItems;
        },

        /**
         * オートコンプリートにセットする
         * @param autoComplete  オートコンプリート
         * @param text          文字列
         * @param postUrl       URL
         * @param maxItems      表示上限
         * @param defaultData   上限数を超えた場合にセットする1件のデータ
         * @param callback      配列をセットする関数
         * @param filterFunc    絞り読み用の関数 nullable
         */
        async setASyncAutoCompleteList(autoComplete, postUrl, text, maxItems, defaultData, callback, filterFunc){
            // 非同期で取得
            autoComplete.loadingFlg = true;
            var list           = await this.getAwaitAutoCompleteList(postUrl, text);
            if(list !== undefined){
                if(list.length <= maxItems){
                    autoComplete.temporarySourceCollection = list;
                    if(typeof filterFunc === 'function'){
                        if(autoComplete.control === undefined){
                            // グリッドでないオートコンプリートにはcontrolが無い
                            callback(filterFunc(autoComplete, autoComplete.text));
                        }else{
                            callback(filterFunc(autoComplete, autoComplete.control.text));
                        }
                        
                    }else{
                        callback(list);
                    }
                    
                }else{
                    // 上限数を超えていた場合
                    callback([defaultData]);
                }
            }
            autoComplete.loadingFlg = false;
        },

        /**
         * リストを取得する
         * @param postUrl
         * @param text
         * @param useLoading
         */
        getAwaitAutoCompleteList(postUrl, text, useLoading) {
            var params = new URLSearchParams();
            params.append('text', text);

            if(useLoading === true){
                this.loading = true;
            }

            var promise = axios.post(postUrl, params)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } else {
                    // 失敗
                }
            }.bind(this))
            .catch(function (error) {
                //alert(MSG_ERROR); 
            }.bind(this))
            .finally(function () {
                if(useLoading === true){
                    this.loading = false;
                }
            }.bind(this));
            return promise;
        },
       
    },

    
    filters: {
        // 日付フォーマット
        date_format: function (date) {
            if (date != null && date != '') {
                date = moment(date).format(FORMAT_DATE);
            }
            return date;
        },
        // 日時フォーマット
        datetime_format: function (datetime) {
            if (datetime != null && datetime != '') {
                datetime = moment(datetime).format(FORMAT_DATETIME);
            }
            return datetime;
        },
        // 3桁ずつカンマ区切り
        comma_format: function (val) {
            if (val == undefined || val == '') {
                return 0;
            }
            if (typeof val !== 'number') {
                val = parseInt(val);
            }
            return val.toLocaleString();
        }
    },
}



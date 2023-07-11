<template>
    <div>

        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" method="post" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label" for="acCustomer">得意先名</label>
                        <wj-auto-complete class="form-control" id="acCustomer" 
                            search-member-path="customer_name"
                            display-member-path="customer_name"
                            selected-value-path="customer_name"
                            :items-source="customerlist"
                            :selected-value="searchParams.customer_name" 
                            :min-length=1
                            :max-items="customerlist.length"
                            :initialized="initCustomer">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label">案件番号</label>
                        <wj-auto-complete class="form-control" id="acMatter" 
                            search-member-path="matter_no"
                            display-member-path="matter_no"
                            selected-value-path="matter_no"
                            :items-source="matterlist"
                            :selected-value="searchParams.matter_no" 
                            :min-length=1
                            :max-items="matterlist.length"
                            :initialized="initMatterNo">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label">案件名</label>
                        <wj-auto-complete class="form-control" id="acMatter" 
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_name"
                            :items-source="matterlist"
                            :selected-value="searchParams.matter_name"
                            :min-length=1
                            :max-items="matterlist.length"
                            :initialized="initMatterName">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label">部門名</label>
                        <wj-auto-complete class="form-control" id="acDepartment" 
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="department_name"
                            :items-source="departmentlist"
                            :selected-value="searchParams.department_name"
                            :min-length=1
                            :selectedIndexChanged="changeIdxDepartment"
                            :max-items="departmentlist.length"
                            :initialized="initDepartment">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2">
                        <label class="control-label">担当者名</label>
                        <wj-auto-complete class="form-control" id="acStaff" 
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="staff_name"
                            :items-source="stafflist"
                            :selected-value="searchParams.staff_name"
                            :min-length=1
                            :max-items="stafflist.length"
                            :initialized="initStaff">
                        </wj-auto-complete>
                    </div>
                </div>

                <div class="row">
                    <label class="col-md-12 text-left">得意先登録日</label>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date1" v-model="searchParams.from_date">
                    </div>
                    <span class="col-md-1 text-center">～</span>
                    <div class="col-md-3">
                        <input type="date" class="form-control" name="date2" v-model="searchParams.to_date">
                    </div>
                </div>

                <div class="row" v-show="isShowRadioBtn">
                    <label class="col-md-12 col-sm-12 col-xs-12">状態</label>
                    <el-radio-group v-model="searchParams.del_flg">
                        <div class="radio col-md-6">
                            <el-radio :label="FLG_OFF">有効</el-radio>
                        </div>
                        <div class="radio col-md-6">
                            <el-radio :label="FLG_ON">無効</el-radio>
                        </div>
                    </el-radio-group>
                </div>

                <div class="pull-right">
                    <button type="button" class="btn btn-primary btn-clear" @click="clear">クリア</button>
                    <button type="submit" class="btn btn-primary btn-search">顧客検索</button>
                </div>

                <div class="clearfix"></div>
            </form>
        </div> 

    

        <!-- 検索結果グリッド -->
        <div class="grid-form">
            <div class="row">
                <div class="col-xs-6">
                    <a type="button" class="btn btn-new" href="/new-customer-edit/new" v-show="isEditable">新規作成</a>
                </div>
                <div class="col-xs-6">
                    <p class="pull-right search-count" style="">検索結果： {{ tableData.length }}件</p>
                </div>
            </div>

            <el-table
                :data="tableData"
                :default-sort = "{prop: 'id', order: 'ascending'}"
                v-loading="loading"
                style="width: 100%">
                <el-table-column
                    prop="id"
                    label="ID"
                    sortable
                    width="100">
                </el-table-column>
                <el-table-column
                    prop="customer_name"
                    label="得意先名"
                    sortable
                    width="200">
                </el-table-column>
                <el-table-column
                    prop="address1"
                    label="住所"
                    sortable
                    width="250">
                </el-table-column>
                <el-table-column
                    prop="tel"
                    label="電話番号"
                    sortable
                    width="180">
                </el-table-column>
                <el-table-column
                    prop="representative_name"
                    label="代表者名"
                    sortable
                    width="130">
                </el-table-column>
                <el-table-column
                    prop="department_name"
                    label="部門"
                    sortable
                    width="150">
                </el-table-column>
                <el-table-column
                    prop="staff_name"
                    label="担当者"
                    sortable
                    width="130">
                </el-table-column>
                <el-table-column
                    label="登録情報"
                    width="100">             
                    <template slot-scope="scope">
                        <a class="btn btn-detail" @click="clickDetailBtn" :href="scope.row.url + urlparam">詳細</a>
                    </template>
                </el-table-column>
                <el-table-column v-show="isEditable"
                    label="編集"
                    width="100">             
                    <template slot-scope="scope">
                        <a class="btn btn-edit" @click="clickDetailBtn" :href="scope.row.url + urlparam + '&mode=2'">編集</a>
                    </template>
                </el-table-column>
            </el-table>
        </div>

    </div>
</template>

<script>
export default {
    data: () => ({
        loading: false,
        isShowRadioBtn: false,
        selectedCustomer: {},
        selectedMatterNo: {},
        selectedMatterName: {},
        selectedDepartment: {},
        selectedStaff: {},

        searchParams: {
            customer_name: '',
            matter_no: '',
            matter_name: '',
            department_name: '',
            staff_name: '',
            from_date: '',
            to_date: '',
            del_flg: 0,
        },
        
        tableData: [],
        urlparam: '',

    }),
    props: {
        isEditable: Number,
        customerlist: Array,
        matterlist: Array,
        departmentlist: Array,
        staffdepartmentlist: Array,
        stafflist: Array,
    },
    created: function(){
        if (this.isEditable == FLG_EDITABLE) {
            // 編集権限有
            this.isShowRadioBtn = true;
        }
        // コンボボックスの先頭に空行追加
        this.customerlist.splice(0, 0, '')
        this.departmentlist.splice(0, 0, '')
        this.stafflist.splice(0, 0, '')
        this.matterlist.splice(0, 0, '')

        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
        }
    },
    mounted: function(){
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            // this.setSearchParams(query, this.searchParams);
            // 検索
            this.search();
        }
    },
    methods: {
        clear: function() {
            // this.searchParams = this.initParams;
            this.selectedCustomer.selectedValue = null;
            this.selectedCustomer.value = null;
            this.selectedCustomer.text = null;

            this.selectedMatterNo.selectedValue = null;
            this.selectedMatterNo.value = null;
            this.selectedMatterNo.text = null;

            this.selectedMatterName.selectedValue = null;
            this.selectedMatterName.value = null;
            this.selectedMatterName.text = null;

            this.selectedDepartment.selectedValue = null;
            this.selectedDepartment.value = null;
            this.selectedDepartment.text = null;

            this.selectedStaff.selectedValue = null;
            this.selectedStaff.value = null;
            this.selectedStaff.text = null;

            this.searchParams = {
                customer_name: '',
                matter_no: '',
                matter_name: '',
                department_name: '',
                staff_name: '',
                from_date: '',
                to_date: '',
                del_flg: 0,
            };
        },
        changeIdxDepartment: function(sender){
            // 部門を選択したら担当者を絞り込む
            var tmpArr = this.staffdepartmentlist;
            var tmpStaff = this.stafflist;
            if (sender.selectedItem) {
                tmpArr = [];
                for(var key in this.staffdepartmentlist) {
                    if (sender.selectedItem.id == this.staffdepartmentlist[key].department_id) {
                        tmpArr.push(this.staffdepartmentlist[key]);
                    }
                }
                tmpStaff = [];
                for(var key in this.stafflist) {
                    for(var i = 0; i < tmpArr.length; i++) {
                        if (tmpArr[i].staff_id == this.stafflist[key].id) {
                            tmpStaff.push(this.stafflist[key]);
                            break;
                        }
                    }
                }      
            }
            this.selectedStaff.itemsSource = tmpStaff;
            this.selectedStaff.selectedIndex = -1;
        },
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('customer_name', this.rmUndefinedBlank(this.selectedCustomer.text));
            params.append('matter_no', this.rmUndefinedBlank(this.selectedMatterNo.selectedValue));
            params.append('matter_name', this.rmUndefinedBlank(this.selectedMatterName.text));
            params.append('department_name', this.rmUndefinedBlank(this.selectedDepartment.text));
            params.append('staff_name', this.rmUndefinedBlank(this.selectedStaff.text));
            params.append('from_date', this.rmUndefinedBlank(this.searchParams.from_date));            
            params.append('to_date', this.rmUndefinedBlank(this.searchParams.to_date));
            params.append('del_flg', this.rmUndefinedBlank(this.searchParams.del_flg));

            axios.post('/new-customer-list/search', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    this.tableData = response.data
                    for (var i =0; i < this.tableData.length; i++) {
                        this.tableData[i].url = 'new-customer-edit/' + this.tableData[i].id
                    }
                }

            }.bind(this))

            .catch(function (error) {
                this.loading = false

                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                location.reload()
                

            }.bind(this))
        },
        // 詳細ボタン
        clickDetailBtn() {
            this.urlparam = '?'
            this.urlparam += '&' + 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedCustomer.selectedValue))
            this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedMatterNo.selectedValue))
            this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedMatterName.selectedValue))
            this.urlparam += '&' + 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedDepartment.selectedValue))
            this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedStaff.selectedValue))
            this.urlparam += '&' + 'from_date=' + encodeURIComponent(this.searchParams.from_date)
            this.urlparam += '&' + 'to_date=' + encodeURIComponent(this.searchParams.to_date)
       },
       // 編集ボタン
        clickEditBtn() {
            this.urlparam = '?'
            this.urlparam += '&' + 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedCustomer.selectedValue))
            this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedMatterNo.selectedValue))
            this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedMatterName.selectedValue))
            this.urlparam += '&' + 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedDepartment.selectedValue))
            this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.selectedStaff.selectedValue))
            this.urlparam += '&' + 'from_date=' + encodeURIComponent(this.searchParams.from_date)
            this.urlparam += '&' + 'to_date=' + encodeURIComponent(this.searchParams.to_date)
            this.urlparam += '&' + QUERY_MODE + '=' + MODE_EDIT
        },

        initCustomer: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedCustomer = sender;
        },
        initMatterNo: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedMatterNo = sender;
        },
        initMatterName: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedMatterName = sender;
        },
        initDepartment: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedDepartment = sender;
        },
        initStaff: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedStaff = sender;
        },
    }
};


</script>

<style>
.btn-clear{
    padding-top: 5px;
}
</style>


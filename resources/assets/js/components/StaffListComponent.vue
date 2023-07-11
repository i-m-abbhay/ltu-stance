<template>
    <div>

        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label">担当者ID</label>
                        <input type="text" class="form-control" v-model="searchParams.id">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">担当者名</label>
                        <input type="text" class="form-control" v-model="searchParams.staff_name">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">担当者かな</label>
                        <input type="text" class="form-control" v-model="searchParams.staff_kana">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label">社員番号</label>
                        <input type="text" class="form-control" v-model="searchParams.employee_code">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">役職</label>
                        <input type="text" class="form-control" v-model="searchParams.position_code">
                    </div>
                    <!-- <div class="col-md-4">
                        <label class="control-label" for="acPosition">役職</label>
                        <wj-auto-complete class="form-control" id="acPosition" :initialized="initPositionId"
                            search-member-path="category_code, value_text_1" 
                            display-member-path="value_text_1"
                            :items-source="positionlist"
                            selected-value-path="value_code" 
                            :selected-value="searchParams.position_code"
                            :max-items="positionlist.length"
                            :min-length=1
                            >
                        </wj-auto-complete>
                    </div> -->
                    <div class="col-md-4">
                        <label class="control-label" for="acDepartment">部門</label>
                        <wj-auto-complete class="form-control" id="acDepartment" :initialized="initDepartmentId"
                            search-member-path="department_name" 
                            display-member-path="department_name"
                            :items-source="departmentlist" 
                            selected-value-path="id" 
                            :selected-value="searchParams.department_id"
                            :max-items="departmentlist.length"
                            :min-length=1
                            >
                        </wj-auto-complete>
                    </div>
                </div>

                <div class="pull-right">
                    <button type="submit" class="btn btn-primary btn-search">検索</button>
                </div>

                <div class="clearfix"></div>
            </form>
        </div> 

    

        <!-- 検索結果グリッド -->
        <div class="grid-form">
            <div class="row">
                <div class="col-xs-6">
                    <a type="button" class="btn btn-warning btn-new" href="/staff-edit/new" v-show="isEditable">新規作成</a>
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
                    width="80">
                </el-table-column>
                <el-table-column
                    prop="staff_name"
                    label="担当者名"
                    sortable
                    width="200">
                </el-table-column>
                <el-table-column
                    prop="staff_kana"
                    label="担当者かな"
                    sortable
                    width="200">
                </el-table-column>
                <el-table-column
                    prop="employee_code"
                    label="社員番号"
                    sortable
                    width="120">
                </el-table-column>
                <el-table-column
                    prop="position_code"
                    label="役職"
                    sortable
                    width="120">
                </el-table-column>
                <el-table-column
                    prop="department_name"
                    label="部門名"
                    sortable
                    width="150">
                </el-table-column>
                <el-table-column
                    prop="email"
                    label="メールアドレス"
                    sortable
                    width="230">
                </el-table-column>
                <el-table-column
                    label="操作"
                    width="150">             
                    <template slot-scope="scope">
                        <a class="btn btn-info btn-detail" @click="clickDetailBtn" :href="scope.row.url + urlparam">詳細</a>
                        <a class="btn btn-warning btn-edit" @click="clickEditBtn" :href="scope.row.url + urlparam">編集</a>
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

        selectedPositionId: {},
        selectedDepartmentId: {},

        searchParams: {
            id: '',
            staff_name: '',
            staff_kana: '',
            employee_code: '',
            position_code: '',
            department_id: '',
        },
        
        tableData: [],
        urlparam: '',

    }),
    props: {
        departmentlist: Array,
        positionlist: Array,
        isEditable: Number,
    },
    created: function(){
        // 拠点コンボボックスの先頭に空行追加
        this.departmentlist.splice(0, 0, '')
        this.positionlist.splice(0, 0, '')
    },
    mounted: function(){
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            this.setSearchParams(query, this.searchParams);
            // 検索
            this.search();
        }
    },
    methods: {
        search() {
            this.loading = true

            // Wijmoオートコンプリート
            // 項目選択して検索　⇒　その後検索条件を消して検索すると、
            // searchParamsにnullが入り正常に検索出来ない
            // if (this.selectedPositionId.selectedValue != null) {
            //     this.searchParams.position_code = this.selectedPositionId.selectedValue;
            // } else {
            //     this.searchParams.position_code = '';
            // }

            if (this.selectedDepartmentId.selectedValue != null) {
                this.searchParams.department_id = this.selectedDepartmentId.selectedValue;
            } else {
                this.searchParams.department_id = '';
            }
            
            
            var params = new URLSearchParams();
            params.append('id', this.searchParams.id);
            params.append('staff_name', this.searchParams.staff_name);
            params.append('staff_kana', this.searchParams.staff_kana);
            params.append('employee_code', this.searchParams.employee_code);
            params.append('position_code', this.searchParams.position_code);
            params.append('department_id', this.searchParams.department_id);

            axios.post('/staff-list/search', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    this.tableData = response.data
                    for (var i =0; i < this.tableData.length; i++) {
                        this.tableData[i].url = 'staff-edit/' + this.tableData[i].id
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
            this.urlparam += 'id=' + encodeURIComponent(this.searchParams.id)
            this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.searchParams.staff_name)
            this.urlparam += '&' + 'staff_kana=' + encodeURIComponent(this.searchParams.staff_kana)
            this.urlparam += '&' + 'employee_code=' + encodeURIComponent(this.searchParams.employee_code)
            this.urlparam += '&' + 'position_code=' + encodeURIComponent(this.searchParams.position_code)
            this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.searchParams.department_id)
       },
        clickEditBtn() {
            this.urlparam = '?'
            this.urlparam += 'id=' + encodeURIComponent(this.searchParams.id)
            this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.searchParams.staff_name)
            this.urlparam += '&' + 'staff_kana=' + encodeURIComponent(this.searchParams.staff_kana)
            this.urlparam += '&' + 'employee_code=' + encodeURIComponent(this.searchParams.employee_code)
            this.urlparam += '&' + 'position_code=' + encodeURIComponent(this.searchParams.position_code)
            this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.searchParams.department_id)
            this.urlparam += '&' + QUERY_MODE + '=' + MODE_EDIT
        },

        initPositionId: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedPositionId = sender;
        },
        initDepartmentId: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedDepartmentId = sender;
        },
    }
};


</script>

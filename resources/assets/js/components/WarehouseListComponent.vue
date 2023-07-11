<template>
    <div>

        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label">倉庫ID</label>
                        <input type="text" class="form-control" v-model="searchParams.id">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">倉庫名</label>
                        <input type="text" class="form-control" v-model="searchParams.warehouse_name">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label" for="acBase">拠点</label>
                        <wj-auto-complete class="form-control" id="acBase" 
                            search-member-path="base_name, base_short_name" 
                            display-member-path="base_name"
                            :items-source="baselist" 
                            selected-value-path="id" 
                            :selected-value="searchParams.select_base_id"
                            :max-items="baselist.length"
                            :min-length=1
                            :initialized="initBaseId">
                        </wj-auto-complete>
                    </div>
                </div>

                <div class="pull-right">
                    <button type="button" class="btn btn-primary btn-clear" @click="clear">クリア</button>
                    <button type="submit" class="btn btn-primary btn-search">検索</button>
                </div>

                <div class="clearfix"></div>
            </form>
        </div> 

    

        <!-- 検索結果グリッド -->
        <div class="grid-form">
            <div class="row">
                <div class="col-xs-6">
                    <a type="button" class="btn btn-warning btn-new" href="/warehouse-edit/new" v-show="isEditable">新規作成</a>
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
                    prop="warehouse_name"
                    label="倉庫名"
                    sortable
                    width="500">
                </el-table-column>
                <el-table-column
                    prop="base_name"
                    label="拠点名"
                    sortable
                    width="500">
                </el-table-column>
                <el-table-column
                    label="操作"
                    width="200">             
                    <template slot-scope="scope">
                        <a class="btn btn-detail" @click="clickDetailBtn" :href="scope.row.url + urlparam">詳細</a>
                        <a class="btn btn-edit" @click="clickEditBtn" :href="scope.row.url + urlparam">編集</a>
                        <a class="btn btn-edit" @click="clickEditBtn" :href="scope.row.shelfUrl + urlparam">棚設定</a>
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

        selectedBaseId: {},

        searchParams: {
            id: '',
            warehouse_name: '',
            select_base_id: '',
        },
        
        tableData: [],
        urlparam: '',

    }),
    props: {
        baselist: Array,
        isEditable: Number,
    },
    created: function(){
        // 拠点コンボボックスの先頭に空行追加
        this.baselist.splice(0, 0, '')

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
        clear: function() {
            // this.searchParams = this.initParams;
            this.selectedBaseId.selectedValue = null;
            this.selectedBaseId.value = null;
            this.selectedBaseId.text = null;
            this.searchParams = {
                id: '',
                warehouse_name: '',
                select_base_id: '',
            };
        },
        search() {
            this.loading = true

            if (this.selectedBaseId.selectedValue != null) {
                this.searchParams.select_base_id = this.selectedBaseId.selectedValue;
            } else {
                this.searchParams.select_base_id = '';
            }

            var params = new URLSearchParams();
            params.append('id', this.searchParams.id);
            params.append('warehouse_name', this.searchParams.warehouse_name);
            params.append('select_base_id', this.searchParams.select_base_id);

            axios.post('/warehouse-list/search', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    this.tableData = response.data
                    for (var i =0; i < this.tableData.length; i++) {
                        this.tableData[i].url = 'warehouse-edit/' + this.tableData[i].id
                        this.tableData[i].shelfUrl = 'shelf-number-edit/' + this.tableData[i].id
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
            this.urlparam += '&' + 'warehouse_name=' + encodeURIComponent(this.searchParams.warehouse_name)
            this.urlparam += '&' + 'select_base_id=' + encodeURIComponent(this.searchParams.select_base_id)
       },
        clickEditBtn() {
            this.urlparam = '?'
            this.urlparam += 'id=' + encodeURIComponent(this.searchParams.id)
            this.urlparam += '&' + 'warehouse_name=' + encodeURIComponent(this.searchParams.warehouse_name)
            this.urlparam += '&' + 'select_base_id=' + encodeURIComponent(this.searchParams.select_base_id)
            this.urlparam += '&' + QUERY_MODE + '=' + MODE_EDIT
        },

        initBaseId: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.selectedBaseId = sender;
        },
        
    }
};


</script>
<style>
.btn-clear{
    padding-top: 5px;
    height: 33.24px;
}
</style>

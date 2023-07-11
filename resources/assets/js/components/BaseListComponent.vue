<template>
    <div>

        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label">拠点ID</label>
                        <input type="text" class="form-control" v-model="searchParams.id">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">拠点名</label>
                        <input type="text" class="form-control" v-model="searchParams.base_name">
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
                    <a type="button" class="btn btn-warning btn-new" href="/base-edit/new" v-show="isEditable">新規作成</a>
                </div>
                <div class="col-xs-6">
                    <p class="pull-right search-count">検索結果： {{ tableData.length }}件</p>
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
                    prop="base_name"
                    label="拠点名"
                    sortable
                    width="250">
                </el-table-column>
                <el-table-column
                    prop="address1"
                    label="住所1"
                    sortable
                    width="300">
                </el-table-column>
                <el-table-column
                    prop="address2"
                    label="住所2"
                    sortable
                    width="300">
                </el-table-column>
                <el-table-column
                    label="操作"
                    width="140">
                    <template slot-scope="scope">
                        <a class="btn btn-info btn-detail" @click="clickDetailBtn" :href="scope.row.editurl + urlparam">詳細</a>
                        <a class="btn btn-warning btn-edit" v-show="isEditable" @click="clickEditBtn" :href="scope.row.editurl + urlparam">編集</a>
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
        searchParams: {
            id: '',
            base_name: '',
        },
        tableData: [],
        urlparam: ''
    }),
    props: {
        isEditable: Number,
    },
    mounted: function() {
        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
            // 検索
            this.search();
        }
    },
    methods: {
        clear: function() {
            // this.searchParams = this.initParams;
            this.searchParams = {
                id: '',
                base_name: '',
            };
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.searchParams.id);
            params.append('base_name', this.searchParams.base_name);
            axios.post('/base-list/search', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    this.tableData = response.data
                    for (var i = 0; i < this.tableData.length; i++) {
                        this.tableData[i].editurl = 'base-edit/' + this.tableData[i].id
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
            this.urlparam += '&' + 'base_name=' + encodeURIComponent(this.searchParams.base_name)
        },
        // 編集ボタン
        clickEditBtn() {
            this.urlparam = '?'
            this.urlparam += 'id=' + encodeURIComponent(this.searchParams.id)
            this.urlparam += '&' + 'base_name=' + encodeURIComponent(this.searchParams.base_name)
            this.urlparam += '&' + QUERY_MODE + '=' + MODE_EDIT
        }
    }
};

</script>
<style>
.btn-clear{
    padding-top: 5px;
}
</style>

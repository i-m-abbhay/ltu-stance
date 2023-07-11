<template>
  <div>
    <el-table :data="captures" v-loading="loading" style="width: 100%">
      <el-table-column label="" width="125">
        <template slot-scope="scope">
          <div>
            <input
              type="file"
              accept="image/*"
              :ref="'image' + scope.$index"
              @change="uploadFile(scope.$index)"
            />
          </div>
        </template>
      </el-table-column>
      <el-table-column label="証明写真" width="200">
        <template slot-scope="scope">
          <div class="capture">
            <img v-if="images[scope.$index] != null" :src="images[scope.$index]" />
          </div>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="70">
        <template slot-scope="scope">
          <a class="btn btn-info" @click="deletePhoto(scope.$index)">削除</a>
        </template>
      </el-table-column>
    </el-table>
    <!-- 確認ダイアログ -->
    <el-dialog
      title="確認"
      :visible.sync="processDialog"
      width="80%"
      :before-close="handleClose"
    >
      <div class="row">
        <div class="col-md-10">
          保存しますか？
          <br />
        </div>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button @click="processDialog = false">キャンセル</el-button>
        <el-button type="primary" @click="process">実行</el-button>
      </span>
    </el-dialog>
    <div class="col-md-12 text-center">
      <br />
      <button type="button" class="btn btn-warning btn-back" v-on:click="back">
        戻る
      </button>
      <button type="button" class="btn btn-primary btn-save" v-on:click="process">
        実行
      </button>
      <br />
      <br />
    </div>
  </div>
</template>

<script>
import loadImage from "blueimp-load-image";
export default {
  data: () => ({
    video: {},
    canvas: {},
    captures: [null, null],
    images: [null, null],
    searchParams: {
      order_no: "",
    },
    processDialog: false,
    tableData: [],
    IMAGE_MAX_WIDTH: 220,
    IMAGE_MAX_HEIGHT: 220,
    loading: false,
  }),
  props: {},
  created: function () {},
  mounted: function () {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    var query = window.location.search;
    if (query.length > 1) {
      // 検索条件セット
      this.setSearchParams(query, this.searchParams);
    }
  },
  methods: {
    uploadFile(index) {
      var _this = this;
      var file = eval("this.$refs.image" + index + ".files[0]");
      loadImage.parseMetaData(file, (data) => {
        const options = {
          maxHeight: _this.IMAGE_MAX_HEIGHT,
          maxWidth: _this.IMAGE_MAX_WIDTH,
          canvas: true,
        };
        if (data.exif) {
          options.orientation = data.exif.get("Orientation");
        }

        loadImage(
          file,
          async (canvas) => {
            _this.captures[index] = canvas.toDataURL(file.type);
            // data_url形式をblob objectに変換
            const blob = this.base64ToBlob(_this.captures[index], file.type);
            // objectのURLを生成
            const url = window.URL.createObjectURL(blob);
            _this.images.splice(index, 1, url);
          },
          options
        );
      });
    },
    base64ToBlob(base64, fileType) {
      const bin = atob(base64.replace(/^.*,/, ""));
      const buffer = new Uint8Array(bin.length);
      for (let i = 0; i < bin.length; i++) {
        buffer[i] = bin.charCodeAt(i);
      }
      return new Blob([buffer.buffer], {
        type: fileType ? fileType : "image/png",
      });
    },

    // 戻る
    back() {
      var listUrl = "/purchase-delivery-list?order_no=" + this.searchParams["order_no"];
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    handleClose(done) {
      done();
      this.updateCount();
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    // 削除
    deletePhoto(index) {
      //保持してるBase64情報クリア
      this.captures[index] = null;
      this.images.splice(index, 1, null);

      //選択ファイルのクリア
      var obj = eval("this.$refs.image" + index);
      obj.value = "";
    },
    // 実行
    process() {
      //サインへ
      this.loading = true;
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      localStorage.setItem("captures", JSON.stringify(this.captures));
      var listUrl = "/purchase-delivery-sign?order_no=" + this.searchParams.order_no;
      window.onbeforeunload = null;
      location.href = listUrl;
    },
  },
};
</script>
<style>
#canvas {
  display: none;
}
.capture {
  /* display: inline; */
  padding: 5px;
}
.cap {
  background-color: white;
  width: 100px;
  height: auto;
}
</style>

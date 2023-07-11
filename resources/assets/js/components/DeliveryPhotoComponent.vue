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
            <!-- <canvas :ref="'canvas' + scope.$index" width="200" height="200"></canvas> -->
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
    urlparam: "",
    id: "",
    processDialog: false,
    result: "",
    message: "",
    noStreamApiSupport: false,
    tableData: [],
    scanData: [],
    IMAGE_MAX_WIDTH: 220,
    IMAGE_MAX_HEIGHT: 220,
    loading: false,
  }),
  props: {},
  created: function () {},
  mounted: function () {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.scanData = JSON.parse(localStorage.getItem("scanData")) || [];
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

      // var image = new Image();
      // var reader = new FileReader();
      // var _this = this;
      // reader.onload = function (e) {
      //   image.onload = function () {
      //     // 縮小後のサイズを計算する
      //     var width, height;
      //     if (image.width > image.height) {
      //       // ヨコ長の画像は横サイズを定数にあわせる
      //       var ratio = image.height / image.width;
      //       width = _this.IMAGE_MAX_WIDTH;
      //       height = _this.IMAGE_MAX_WIDTH * ratio;
      //     } else {
      //       // タテ長の画像は縦のサイズを定数にあわせる
      //       var ratio = image.width / image.height;
      //       width = _this.IMAGE_MAX_HEIGHT * ratio;
      //       height = _this.IMAGE_MAX_HEIGHT;
      //     }

      //     // 縮小画像を描画するcanvasのサイズを上で算出した値に変更する
      //     var canvas = eval("_this.$refs.canvas" + index);

      //     var ctx = canvas.getContext("2d");

      //     // canvasに既に描画されている画像があればそれを消す
      //     ctx.clearRect(0, 0, _this.IMAGE_MAX_HEIGHT, _this.IMAGE_MAX_HEIGHT);

      //     // canvasに縮小画像を描画する
      //     ctx.drawImage(image, 0, 0, image.width, image.height, 0, 0, width, height);

      //     // canvasから画像をbase64として取得する
      //     _this.captures[index] = canvas.toDataURL("image/jpeg");
      //   };
      //   image.src = e.target.result;
      // };
      // reader.readAsDataURL(file);
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
      var listUrl = "/delivery-list";
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

      // //Canvasクリア
      // var canvas = eval("this.$refs.canvas" + index);
      // var ctx = canvas.getContext("2d");
      // ctx.clearRect(0, 0, this.IMAGE_MAX_HEIGHT, this.IMAGE_MAX_HEIGHT);
      this.images.splice(index, 1,null);

      //選択ファイルのクリア
      var obj = eval("this.$refs.image" + index);
      obj.value = "";
    },
    // 実行
    process() {
      axios
        .post("/delivery-photo/save", {
          tableData: this.tableData,
          captures: this.captures,
        })

        .then(
          function (response) {
            if (response.data) {
              this.loading = false;
              localStorage.setItem("tableData", JSON.stringify(this.tableData));
              localStorage.setItem("scanData", JSON.stringify(this.scanData));
              var listUrl = "/delivery-sign";
              window.onbeforeunload = null;
              location.href = listUrl;
            } else {
              // 失敗
              this.loading = false;
              window.onbeforeunload = null;
              alert(MSG_ERROR);
              location.reload();
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            this.loading = false;
            if (error.response.data.errors) {
              // エラーメッセージ表示
              this.showErrMsg(error.response.data.errors, this.errors);
            } else {
              if (error.response.data.message) {
                alert(error.response.data.message);
              } else {
                alert(MSG_ERROR);
              }
              window.onbeforeunload = null;
              location.reload();
            }
          }.bind(this)
        );
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

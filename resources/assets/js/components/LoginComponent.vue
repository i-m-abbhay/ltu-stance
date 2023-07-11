<template>
  <div>
    <div class="login-form" id="loginform">
      <form
        id="loginform"
        name="loginform"
        class="form-horizontal"
        method="post"
        action="/login/check"
      >
        <div class="col-md-12">
          <h1 class="col-md-11 col-sm-11 text-center">
            <img src="/logo.png" style="height:40px" /> For Internship
          </h1>
          <div class="col-md-6 col-md-offset-2 col-sm-offset-1">
            <div class="form-group row">
              <label class="control-label text-right col-md-4 col-sm-3 col-xs-4">ログインID</label>
              <div
                class="col-md-8 col-sm-6 col-xs-6"
                v-bind:class="[errors.login_id ? 'has-error' : '', errors.error ? 'has-error' : '']"
              >
                <input
                  type="text"
                  class="form-control"
                  v-model="loginUser.input_id"
                  @change="onChange"
                  @keyup.enter="login"
                />
                <span class="text-danger">{{ errors.login_id }}</span>
              </div>
            </div>
            <div class="form-group row">
              <label class="control-label text-right col-md-4 col-sm-3 col-xs-4">パスワード</label>
              <div
                class="col-md-8 col-sm-6 col-xs-6"
                v-bind:class="[errors.password ? 'has-error' : '', errors.error ? 'has-error' : '']"
              >
                <input
                  type="password"
                  class="form-control"
                  v-model="loginUser.input_pw"
                  @change="onChange"
                  @keyup.enter="login"
                />
                <span class="text-danger">{{ errors.password }}</span>
                <span class="text-danger">{{ errors.error }}</span>
              </div>
            </div>

            <div class="col-md-offset-2 col-md-12">
              <div class="text-center">
                <button type="button" class="btn btn-primary" v-on:click="login">ログイン</button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,

    loginUser: {
      input_id: "",
      input_pw: "",
      latitude: null,
      longitude: null,
    },

    errors: {
      login_id: "",
      password: "",
      error: "",
    },
  }),
  mounted: function () {
    // 現在位置を取得する
    navigator.geolocation.getCurrentPosition(this.gotLocation,null,{optionObj:true});
  },
  methods: {
    login() {
      this.loading = true;
      // エラーの初期化
      var errs = this.errors;
      Object.keys(errs).forEach(function (key) {
        errs[key] = "";
      });

      var params = new URLSearchParams();

      params.append("login_id", this.loginUser.input_id);
      params.append("password", this.loginUser.input_pw);
      params.append("latitude", this.loginUser.latitude);
      params.append("longitude", this.loginUser.longitude);

      axios
        .post("/login/check", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              if (response.data.status === true) {
                // TODO: ログイン後の動作
                location.href = response.data.url;
              } else {
                this.errors.error = response.data.msg;
              }
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            if (error.response.data.errors) {
              // TODO: エラーメッセージの格納
              var errList = [];
              var errItems = error.response.data.errors;
              Object.keys(errItems).forEach(function (key) {
                errList[key] = "";
                errItems[key].forEach((val) => {
                  if (errList[key] != "") {
                    errList[key] += "\n";
                  }
                  errList[key] += val;
                });
              });
              // メッセージ表示
              var errs = this.errors;
              Object.keys(errs).forEach(function (key) {
                if (errList[key]) {
                  errs[key] = errList[key];
                }
              });
            } else {
              if (error.response.data.message) {
                alert(error.response.data.message);
              } else {
                alert(MSG_ERROR);
              }
              // location.reload()
            }
            this.loading = false;
          }.bind(this)
        );
    },
    // エラーメッセージ初期化
    onChange() {
      this.errors.login_id = "";
      this.errors.password = "";
    },
    //位置情報が取得できた場合
    gotLocation(position) {
      this.loginUser.latitude = position.coords.latitude;
      this.loginUser.longitude = position.coords.longitude;
    },
  },
};
</script>



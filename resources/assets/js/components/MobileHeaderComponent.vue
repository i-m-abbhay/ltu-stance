<template>
    <div>
        <div class="sidemenu-bar">
            <div class="header"  :class="{'nav-open':isOpen}">
                <nav class="global-nav">
                    <ul class="global-nav__list">
                    <li class="global-nav__item"><a href="/mobile-menu">Home</a></li>
                    <!-- <li class="global-nav__item"><a href="">メニュー3</a></li> -->
                    <!-- <li class="global-nav__item"><a href="">メニュー4</a></li> -->
                    <!-- <li class="global-nav__item"><a href="">メニュー5</a></li> -->
                    <li class="global-nav__item"><a href="/">PC版へ切り替え</a></li>
                    <li class="global-nav__item dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                            <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
                            {{ loginuser.staff_name }}
                            <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <!-- <li role="presentation"><a role="menuitem" tabindex="-1" href="#">パスワード変更</a></li> -->
                            <li role="presentation"><a role="menuitem" tabindex="-1" href="/logout">ログアウト</a></li>
                        </ul>
                    </li>
                    
                    </ul>
                </nav>
                <div class="hamburger" id="js-hamburger" @click="switchMenu" :class="{'nav-open':isOpen}">
                    <span class="hamburger__line hamburger__line--1"></span>
                    <span class="hamburger__line hamburger__line--2"></span>
                    <span class="hamburger__line hamburger__line--3"></span>
                </div>
                <div class="black-bg" id="js-black-bg"></div>
            </div>
            <!-- <mobilesidemenu-component :loginuser="loginuser"></mobilesidemenu-component> -->
        </div>
        <div class="mobile-headline"></div>
        <div class="mobile-footline"></div>
        <div class="mobile-logo"><a href="/mobile-menu"><img src="/mobile_logo.png" /></a></div>
        <svg class="mobile-svg"><use width="79.31" height="44.45" xlink:href="#headerIcon" /></svg>
    </div>
</template>

<script>
export default {
    data: () => ({
		isOpen: false,
    }),
    props: {
        loginuser: Object,
    },
    methods: {
        switchMenu() {
          // メニューを開いていた場合、スクロール禁止にする
          var menu = $('.global-nav'),
              body = $(document.body),
              menuWidth = menu.outerWidth();
          $('html').toggleClass('scroll-prevent')

          if (!$('div.header').hasClass('nav-open')) {
              body.animate({'right' : menuWidth }, 200);
              menu.animate({'right' : 0 }, 200);                
          } else {
              menu.animate({'right' : -menuWidth }, 200);
              body.animate({'right' : 0 }, 200);
          }

          this.isOpen = !this.isOpen;
        },
    },
}
</script>

<style>
.scroll-prevent {
  /*動き固定*/
  position: fixed;
  /*奥行きを管理*/
  z-index: -1;
  /*下2つで背景を元のサイズのまま表示することができる*/
  width: 100%;
  height: 100%;
}
.header {
  position: absolute;
  left: 0;
  top: 35px;
  width: 100%;
  /* height: 40px; */
  background-color: #fff;
  box-shadow: 0 2px 6px rgba(0,0,0,.16);
}
.global-nav {
  position: fixed;
  right: -320px; /* これで隠れる */
  top: 0;
  width: 300px; /* スマホに収まるくらい */
  height: 100vh;
  padding-top: 40px;
  background-color: #fff;
  transition: all .6s;
  z-index: 200;
  overflow-y: auto; /* メニューが多くなったらスクロールできるように */
  padding-top: 70px;
}
.hamburger {
  position: absolute;
  right: 0;
  top: 0;
  width: 40px; /* クリックしやすいようにちゃんと幅を指定する */
  height: 40px; /* クリックしやすいようにちゃんと高さを指定する */
  cursor: pointer;
  z-index: 300;
}
.global-nav__list {
  margin: 0;
  padding: 0;
  list-style: none;
}
.global-nav__item {
  text-align: center;
  padding: 0 14px;
}
.global-nav__item a {
  display: block;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
  text-decoration: none;
  color: #111;
}
.global-nav__item a:hover {
  background-color: #eee;
}
.hamburger__line {
  position: absolute;
  left: 11px;
  width: 18px;
  height: 1px;
  background-color: #111;
  transition: all .6s;
}
.hamburger__line--1 {
  top: 14px;
}
.hamburger__line--2 {
  top: 20px;
}
.hamburger__line--3 {
  top: 26px;
}
.black-bg {
  position: fixed;
  left: 0;
  top: 0;
  width: 100vw;
  height: 100vh;
  z-index: 100;
  background-color: #000;
  opacity: 0;
  visibility: hidden;
  transition: all .6s;
  cursor: pointer;
}
/* 表示された時用のCSS */
.nav-open .global-nav {
  right: 0;
}
.nav-open .black-bg {
  opacity: .8;
  visibility: visible;
}
.nav-open .hamburger__line--1 {
  transform: rotate(45deg);
  top: 20px;
}
.nav-open .hamburger__line--2 {
  width: 0;
  left: 50%;
}
.nav-open .hamburger__line--3 {
  transform: rotate(-45deg);
  top: 20px;
}
</style>
<template>
    <div 
        v-loading="loading"
        :element-loading-text=loadingMsg
        element-loading-background="rgba(0, 0, 0, 0.8)"
        v-bind:class="{ 'loading': loading }"
    ></div>
</template>

<style>
.loading {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
}
</style>

<script>
export default {
    data: () => ({
        loadingMsg: MSG_LOADING,
    }),
    props: {
        loading: Boolean,
    },
}
</script>
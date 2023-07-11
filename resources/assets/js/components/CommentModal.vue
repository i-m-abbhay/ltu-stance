<template>
    <el-dialog width="400px"
        :visible.sync="$props.show"
        :title="$props.header"
        :before-close="hideModal"
    >
        <div class="dialog-body">
            <slot name="body"></slot>
        </div>
        <div class="dialog-footer">
            <slot name="footer"></slot>
        </div>
    </el-dialog>
</template>

<script>
export default {
  props: {
    show: Boolean,
    header: String,
  },
  methods: {
    hideModal() {
      this.$emit('onCommentModal')
    }
  }
}
</script>
<style>
/*********************************
    コメントダイアログ
**********************************/
.dialog-footer{
    text-align: center;
}
</style>
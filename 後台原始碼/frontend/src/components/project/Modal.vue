<script>
export default {
  props: {
    modalMessage: String,
  },
}
</script>
<template>
  <div
    class="modal modal-cover fade"
    tabindex="-1"
    aria-hidden="false"
     id="modal"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">系统</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p id="modalMessage">{{modalMessage}}</p>
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="modal-cancel">取消</button>
        <button type="button" class="btn btn-primary" id="modal-ok" @click="$emit('someEvent')" data-bs-dismiss="modal">确定</button>
      </div>
      </div>

    </div>
  </div>
</template>
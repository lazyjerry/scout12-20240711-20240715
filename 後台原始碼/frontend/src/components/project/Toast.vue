<script>
export default {
  props: {
    toastMessage: String,
  },
}
</script>
<template>
  <!-- toasts-container -->
  <div class="toasts-container">
    <div class="toast fade hide mb-3 " data-autohide="true" id="toast-1"  style="z-index: 5">
      <div class="toast-header">
        <i class="far fa-bell text-muted me-2"></i>
        <strong class="me-auto">系统</strong>
        <small></small>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div class="toast-body" id="toastBody">
        {{toastMessage}}
      </div>
    </div>
  </div>
  <!-- toasts-container -->
</template>
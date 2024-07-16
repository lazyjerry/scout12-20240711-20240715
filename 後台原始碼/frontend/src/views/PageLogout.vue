<script>
import { useAppOptionStore } from '@/stores/app-option';
import { useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
const appOption = useAppOptionStore();


export default {
  data(){
    return {};
  },
  watch:{

  },
  mounted() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = true;
    appOption.appContentClass = 'p-0';

    let loginToken = localStorage.getItem(appVariable.key.accountSession);
    if(null == loginToken){
     
      this.$router.next('/login');
    }else{
      this.$router.next('/');
    }
  },
  beforeUnmount() {
    appOption.appSidebarHide = false;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';
  },
  methods: {
    confirm: function(){
      //localStorage.removeItem(appVariable.key.accountSession);
      localStorage.clear();
      this.$router.go(0)
    }
  }
}
</script>

<template>
  <!-- BEGIN login -->
  <div class="login">
    <!-- BEGIN login-content -->
    <div class="login-content">
      <form  method="POST" name="login_form">
        <h1 class="text-center">確認登出</h1>
        <div class="text-inverse text-opacity-50 text-center mb-4">
          請確認是否登出呢？
        </div>
        <button v-on:click="confirm" class="btn btn-outline-theme btn-lg d-block w-100 fw-500 mb-3" :disabled="loading" >確定登出</button>
        
      </form>
    </div>
    <!-- END login-content -->
  </div>
  <!-- END login -->
</template>
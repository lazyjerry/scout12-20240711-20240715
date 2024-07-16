<script>
import { Toast, Modal } from 'bootstrap';

import { useAppOptionStore } from '@/stores/app-option';
const appOption = useAppOptionStore();

import { useRouter, RouterLink } from 'vue-router';
import { QrStream } from 'vue3-qr-reader';

import axios from 'axios';
import { useAppVariableStore } from '@/stores/app-variable';
const appVariable = useAppVariableStore();
import toast from '@/components/project/Toast.vue';
import modal from '@/components/project/Modal.vue';

import * as helper from '@/composables/helper.js';

export default {
  setup () {
    
    let data = {
      shopNo: "",
      scanResult: "",
      forceReload: 0,
      toastMessage:"",
      modalMessage:"",
    };   
    return data;
  },
  components: {
    // https://www.npmjs.com/package/qrcode-reader-vue3
    QrStream,
    "toast-component": toast,
    "modal-component": modal,
  },
  mounted() {
    appOption.appSidebarHide = false;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';

    // login
    helper.checkLogedIn(axios, this.$router, appOption, appVariable);

    // shopKey
    let workshopSessionsStr = localStorage.getItem(appVariable.key.workshopSessions);
    let workshopSessions = workshopSessionsStr.split(",");
    let shopNo = helper.getShopNo(appVariable);
    for (var i = 0; i < workshopSessions.length; i++) {
      // 要依照時間處理可顯示的選項
      let isExp = helper.isSessionExpired(""+workshopSessions[i]);
      if(isExp){
        continue;
      }
      let selected = (shopNo == workshopSessions[i]) ? "selected" : "";
      var item = '<option value="' + workshopSessions[i] + '" ' + selected + '>' + workshopSessions[i] + '</option>';
      if("" != selected){
        this.shopNo = shopNo;
      }

     $('#shopNoArea').append(item);
    };

    let trigger = document.getElementById('trigger');
    let audio = document.getElementById('audio');
    trigger.addEventListener('touchstart', () => {
      audio.play();
    });

  },
  beforeUnmount() {
    appOption.appSidebarHide = false;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';
  },
  methods: {
    onDecode: function(str){

      console.log(str);

      this.scanResult = str;
      document.getElementById("scanResult").value = this.scanResult;
      document.getElementById("scanResult").classList.add('border-green');

    },
    showToast(message) {
      this.toastMessage = message;

      document.getElementById("toastBody").innerHTML = this.toastMessage;
      let toast = new Toast(document.getElementById("toast-1"));
      toast.show();
    },
    enterData: function(){
      this.shopNo = $('#shopNoArea').find(":selected").val();
      this.shopNo = this.shopNo.trim();
      this.scanResult = $('#scanResult').val();
      this.scanResult = this.scanResult.trim();
      let isError = false;
      if("" == this.shopNo){
        isError = true;
        this.showToast("請輸入場次編號");
      }else if("" == this.scanResult){
        isError = true;
        this.showToast("清輸入學員編號");
      }
      if(isError){
        this.forceReload ++;
        this.scanResult = "";
        document.getElementById("scanResult").classList.remove('border-green');
        document.getElementById("scanResult").value = "";
        return ;
      }
      this.forceReload ++;
      helper.saveShopNo(appVariable,this.shopNo);
      console.log(this.shopNo+", "+this.scanResult);
    
      // 檢查登入、報到 
      let loginToken = localStorage.getItem(appVariable.key.accountSession);
      if(null == loginToken){
        alert("尚未登入");
        this.$router.push('/login');
        return;
      }

      this.modalMessage = "一秒後自動確認 <br><br> <b>場次：" + this.shopNo + "<br> 學員編號：" + this.scanResult+"</b>";
      document.getElementById("modalMessage").innerHTML = this.modalMessage;
      this.modal = new Modal(document.getElementById('modal'),{backdrop: 'static', keyboard: false});
      this.modal.show();

      setTimeout(function(){
        
        if(document.querySelector("#modal.show") != null){
          
          document.getElementById('modal-ok').click();
        }
      },1000);



      // this.showToast("請稍等");
      // setTimeout(function(scope,loginToken){
      //   scope.doSendData(loginToken);
      // }, 900, this, loginToken);
    },
    createTouchstartEventAndDispatch (el) {
      let event = document.createEvent('Events')
      event.initEvent('touchstart', true, true)
      el.dispatchEvent(event)
    },
    doSendData:function(){

      let loginToken = localStorage.getItem(appVariable.key.accountSession);
      axios.post(appOption.project.apiURL + '/', {
        token: loginToken,
        shopNo: this.shopNo,
        memberId: this.scanResult,
        action:"putSignOut"
      }).then((response) => {
        if(response.data.result.checkSuccess){
          this.showToast("簽出成功！");
        }else{
          alert(response.data.result.errorMessage);
        }

        this.forceReload ++;
        this.scanResult = "";
        document.getElementById("scanResult").classList.remove('border-green');
        document.getElementById("scanResult").value = "";
      });
      // helper.playAudio();
      let trigger = document.getElementById('trigger');
      this.createTouchstartEventAndDispatch(trigger);
    }
  }
}
</script>
<template>
  <!-- BEGIN container -->
  <div class="container">
    <!-- BEGIN row -->
    <div class="row justify-content-center">
      <!-- BEGIN col- -->
      <div class="col-xl-6 col-lg-6 col-md-10 col-12">
        <!-- BEGIN row -->
        <div class="row">
          <!-- BEGIN in row -->
          <div class="col-xl-12">
            <ul class="breadcrumb">
              <li class="breadcrumb-item active"><a href="/">簽退</a></li>
            </ul>
          
            <h1 class="page-header">
              簽退 <small>請輸入場次編號與學員編號</small>
            </h1>
          
            <hr class="mb-4" />
        
            <div class="mb-5">
              <card>
                <card-body>
                  <div>
                    <div class="mb-3">
                      <label class="form-label">場次編號<span class="text-danger">*</span></label>
                      <select class="form-select" id="shopNoArea">
                        <option value="">請選擇一個場次</option>
                      </select>
                      
                    </div>
                    <div class="mb-3">
                      <label class="form-label">學員編號<span class="text-danger">*</span></label>
                      <input type="text" class="form-control form-control-lg bg-white bg-opacity-5"  placeholder="請掃描/輸入編號" v-model="scanResult" id="scanResult" required="required" maxlength="6" onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"/>
                    </div>

                    <button type="button" class="btn btn-outline-theme btn-lg d-block w-100 fw-500 mb-3 mt-3" v-on:click="enterData">送出</button>

                    <qr-stream @decode="onDecode" :key="forceReload" ></qr-stream>
                   
                    
                  </div>
                  <toast-component :toastMessage="toastMessage"/>
                </card-body>
              </card>
            </div>
          
          </div>
          <!-- END in row-->
          
        </div>
        <!-- END row -->
      </div>
      <!-- END col-12 -->
    </div>
    <!-- END row -->
  </div>
  <!-- END container -->
  <modal-component :modalMessage="modalMessage" @some-event="doSendData"/> 
</template>

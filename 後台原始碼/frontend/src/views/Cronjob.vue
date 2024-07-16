<script>
import { useAppOptionStore } from '@/stores/app-option';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
const appOption = useAppOptionStore();

import { useAppVariableStore } from '@/stores/app-variable';
const appVariable = useAppVariableStore();

import * as helper from '@/composables/helper.js';

// UGLY 這是偷懶做的，應該要把這個功能寫在後端
// 這個頁面要再活動期間開啟以後放著讓他執行，請確保 JS 背景運作正常。
export default {
  data(){
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = true;
    appOption.appContentClass = 'p-0';

    let infomation = "";
      
    const failureCallback = function(data){
      console.log("操作失敗，請確認參數是否正確");
    }
    const errorCallback = function(){
      console.log("網路錯誤，請稍後再試");
    }
    let renewData = {};
    let startTime = 0;
    const route = useRoute();
    return {
      infomation,
      failureCallback,
      errorCallback,
      renewData,
      startTime,
      route,
    };
  },
  watch:{

  },
  mounted() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = true;
    appOption.appContentClass = 'p-0';

    this.infomation = "十秒後啟動";
    setTimeout(function(vueScope){
        vueScope.init();
      }, 10000,this)
  
  },
  beforeUnmount() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = true;
    appOption.appContentClass = '';
  },
  methods: {
    
    init: function(){
      const vueScope = this;
    
      const url = appOption.project.apiURL + '/api/getConfig';
      
      
      
      
      console.log(vueScope.route)
      let time = (!vueScope.route.query || !vueScope.route.query.time) ? "":vueScope.route.query.time;
      if(!time.includes("T") && !time.includes(" ") ){
        const dateObj = new Date();
        console.log(dateObj);
        const pMonth        = (dateObj.getMonth() + 1).toString().padStart(2,"0");
        const pDay          = dateObj.getDate().toString().padStart(2,"0");
        const newPaddedDate = dateObj.getFullYear() +"-"+pMonth+"-"+pDay;
        console.log(newPaddedDate);
        time = newPaddedDate + "T"+time;
      }
      
      console.log("time " + time);
      
      const data = { time: time};
      vueScope.startTime = Date.now();

      const successCallback = function(rowData){
        // 成功以後跑系統
        console.log("******  ******");
        console.log(rowData.data);
        vueScope.infomation = "-- START --"+"\n";
        vueScope.startCron(rowData.data);
      };

      helper.post(axios, url, "", data, successCallback, this.failureCallback, this.errorCallback);
    },
    syncWrokshops: function(colsestSession, workshopUsername, workshop, url, successCallback, vueScope){

          vueScope.infomation += "\n查到場次 - 開始同步";
          // 執行同步場次行為
          let data = { 
            'workshop': workshopUsername,
            'session': colsestSession
          };
          console.log("++++++++");
          console.log(data);

          let diff = Date.now() - vueScope.startTime;
          console.log("花費 " + (diff/1000) +"秒");
          helper.post(axios, url, "", data, successCallback, vueScope.failureCallback, vueScope.errorCallback);
          vueScope.infomation += " - 同步結束\n";
    },
    startCron: async function(infomation){
      let cronStartTime = Date.now();

      const colsestSession = infomation.colsestSession;
      const workshops = infomation.workshops;
      const sessions = infomation.sessions;
      console.log(infomation);
      const vueScope = this;

      const url = appOption.project.apiURL + '/api/syncSignData';
      const successCallback = function(rowData){
          vueScope.infomation += "\n" + JSON.stringify(rowData.data);
      };

      if("" != colsestSession){
        // 抓得到場次的狀態
        vueScope.infomation += "\n預計同步場次："+ colsestSession;
        let count = 1;
        for (const [workshopUsername, workshop] of Object.entries(workshops)) { 
          console.log("+-+- 開始同步 場次："+ colsestSession + ", 工作坊："+ workshopUsername + ", 該場次工作坊數量：" + count + " +-+-");

          vueScope.infomation += "\n工作坊編號："+ workshopUsername + ", 確認場次："+colsestSession;
          let workshopSessions = workshop.workshop_sessions.split(',');
          // 檢查工作坊的 session 是否包含該場次
          if(-1 != workshopSessions.indexOf(colsestSession)){
          
            count = count + 1;
            vueScope.syncWrokshops(colsestSession, workshopUsername, workshop, url, successCallback, vueScope);
            await new Promise(r => setTimeout(r, 1000));

          }else{
            console.log("---- 沒有場次 ---");
          }

          let diffTime = Date.now() - cronStartTime;
          console.log("+-+- 單次耗時："+ diffTime +" 秒 +-+-");
          vueScope.infomation += "\n單次耗時："+ (diffTime/1000) +" 秒";

        };
      }else{
        // 抓不到場次的狀態 全 session 排查
        
        vueScope.infomation += "\n目前偵測不到場次，採工作坊最近一次同步模式";
        
        for (const [index, session] of Object.entries(sessions)) { 
          console.log(index, session);
          
          vueScope.infomation += "\n預計同步場次："+ session;
          let count = 1;
          for (const [workshopUsername, workshop] of Object.entries(workshops)) { 
            console.log("+-+- 開始同步 場次："+ session + ", 工作坊："+ workshopUsername + ", 該場次工作坊數量：" + count + " +-+-");

            vueScope.infomation += "\n工作坊編號："+ workshopUsername + ", 確認場次："+session;
            let workshopSessions = workshop.workshop_sessions.split(',');
            // 檢查工作坊的 session 是否包含該場次
            if(-1 != workshopSessions.indexOf(session)){
            
              await new Promise(r => setTimeout(r, 1000));
              count = count + 1;
              vueScope.syncWrokshops(session, workshopUsername, workshop, url, successCallback, vueScope);
              await new Promise(r => setTimeout(r, 1000));

            }else{
              console.log("---- 沒有場次 ---");
            }

            let diffTime = Date.now() - cronStartTime;
            console.log("+-+- 單次耗時："+ diffTime +" 秒 +-+-");
            vueScope.infomation += "\n單次耗時："+ (diffTime/1000) +" 秒";
          };

          await new Promise(r => setTimeout(r, 1000));
        }
      }
      console.log("-------");
      console.log("******  ******");

      vueScope.infomation = "現在時間" + helper.getFullTime() + vueScope.infomation;
      
      // delay 500 豪秒
      await new Promise(r => setTimeout(r, 500));

      let isOnce = (!vueScope.route.query || !vueScope.route.query.isOnce) ? "":vueScope.route.query.isOnce;

      if("" == isOnce){
        const delay = 10; // 要重啟的秒數
        let diff = Date.now() - vueScope.startTime;
        vueScope.infomation = "花費 " + (diff/1000) + "秒操作完畢 " + delay + " 秒後刷新" + vueScope.infomation;
        setTimeout(function(){
          location.href = location.href;
        }, delay * 1000);
      }
    },  
  }
}
</script>

<template>
  <!-- BEGIN login -->
  <div class="login">
    <!-- BEGIN row -->
    <div class="row">
      {{infomation}}
    </div>
    <!-- END row -->
  </div>
  <!-- END login -->
</template>
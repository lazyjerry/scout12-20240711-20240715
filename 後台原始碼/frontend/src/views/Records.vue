<script>
import { defineComponent, reactive, computed } from 'vue';


// import vueTable from '@/components/plugins/VueTable.vue';
import VueTableLite from "vue3-table-lite/ts";
import axios from 'axios';
import { useAppVariableStore } from '@/stores/app-variable';
import datepicker from '@/components/plugins/Datepicker.vue'

const appVariable = useAppVariableStore();

import { useAppOptionStore } from '@/stores/app-option';
const appOption = useAppOptionStore();

import * as helper from '@/composables/helper.js';
import * as bootstrap from 'bootstrap';


import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

import * as namingStyle from 'naming-style';
// https://www.npmjs.com/package/naming-style


export default {
  data () {
    // Table config
    const table = reactive({
      
      isLoading: false,
      columns: [
        {
          label: "ID",
          field: "id",
          headerStyles:{minWidth:"120px"},
          sortable: true,
        },
        {
          label: "工作坊編號",
          field: "workshop_username",
          headerStyles:{minWidth:"150px"},
          sortable: true,
        },
        {
          label: "工作坊名稱",
          field: "workshop_name",
          headerStyles:{minWidth:"150px"},
          sortable: false,
        },
        {
          label: "場次",
          field: "workshop_session",
          headerStyles:{minWidth:"120px"},
          sortable: true,
        },
        {
          label: "學員編號",
          field: "member_num",
          headerStyles:{minWidth:"120px"},
          sortable: true,
        },
        {
          label: "學員名稱",
          field: "member_name",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        {
          label: "簽到時間",
          field: "sign_in",
          headerStyles:{minWidth:"200px"},
          sortable: true,
        },
        {
          label: "簽出時間",
          field: "sign_out",
          headerStyles:{minWidth:"200px"},
          sortable: true,
        },
        {
          label: "聯絡電話",
          field: "member_phone",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        {
          label: "緊急聯絡人",
          field: "member_contact_name",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        {
          label: "緊急聯絡電話",
          field: "member_contact_phone",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        {
          label: "分營區",
          field: "member_area",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        {
          label: "團名",
          field: "scout_name",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        {
          label: "地區＋團次",
          field: "scout_num",
          headerStyles:{minWidth:"120px"},
          sortable: false,
        },
        
      ],
      rows: [],
      totalRecordCount: 0,
      sortable: {
        order: "id",
        sort: "asc",
      },
      messages:{
        noDataAvailable: " 0 筆數",
        pagingInfo: "現在顯示 {0} 到 {1}筆 共{2}筆",
        pageSizeChangeLabel: "每頁筆數:",
        gotoPageLabel: "現在頁數:",
      },
      isReSearch:false,
      page:1,
      pageOptions: [
        { value: 100, text: 100},
        { value: 300, text: 300 },
        { value: 500, text: 500},
        { value: 1000, text: 1000}
      ]
    });

    // login
    helper.checkLogedIn(axios, this.$router, appOption, appVariable);
    
    return {
      table,
      id:"",
      workshopName:"",
      workshopSession:"",
      workshopUsername:"",
      memberNum:"",
      memberName:"",
      memberContactName:"",
      memberArea:"",
      scoutName:"",
      scoutNum:"",
      startTime: helper.getFullDate("00:00:00"),
      endTime:  helper.getFullDate("23:59:59"),
      isSignIn:false,
      isSignOut:false,
    }
  },
  watch:{

  },
  components:{
    VueTableLite,
    datepicker: datepicker,
  },
  mounted() {
    appOption.appSidebarHide = false;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';

  },
  beforeUnmount() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';
  },
  methods: {
    
    rowClicked: function(row){
      
       
      for (const [key, value] of Object.entries(row)) {
          let newKey = namingStyle.camel(key);
          if($('#edit input[data-id="'+newKey+'"]').length > 0 ){
            console.log(key,value);
            $('#edit input[data-id="'+newKey+'"]').val(value);
          }
      }
      var firstTabEl = document.querySelector('a.nav-link[href="#edit"]');
      var firstTab = new bootstrap.Tab(firstTabEl);
      firstTab.show();
    },
    modifyData: function(action){
      let errors = [];
      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      let data = {action:action};
      $('#'+action+' input').each(function(){
        let key = $(this).data('id');
        let value = $(this).val().trim();

        if($(this).prop('required') && '' == value){
          errors.push("必要資料空缺，請確認後再次送出");
        }
        
        data[key]=value;
      })

      console.log(data);

      if(errors.length > 0){
        for (const [key,message] of Object.entries(errors)) {
          toast.error(message,appVariable.toastStyle);
        };
        return;
      }

      const successCallback = function(rowData){
        
        console.log(rowData);
        toast.success("操作成功，請重新搜尋",appVariable.toastStyle);
        vueScope.refreshData();
      };
      const failureCallback = function(data){
        alert("操作失敗，請確認參數是否正確");
      }
      const errorCallback = function(response){
        alert("網路錯誤，請稍後再試");
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },
    sync: function(){
      if("" == this.workshopSession){
        alert("無法同步，場次編號為必要欄位");return;
      }else if("" == this.workshopUsername){
        alert("無法同步，工作坊編號為必填欄位");return;
      }

      if(!confirm("將添加資料，如已有資料將不與變更，確認繼續執行？")){
        return;
      }

      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      const data = {
        session: this.workshopSession,
        workshop: this.workshopUsername,
        action:"sync",
      };
      const successCallback = function(rowData){
        toast.success("操作成功",appVariable.toastStyle);
        vueScope.refreshData();
      };
      const failureCallback = function(data){
        alert("操作失敗，請確認參數是否正確");
        this.table.isLoading = false
      }
      const errorCallback = function(response){
        alert("網路錯誤，請稍後再試");
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },
    downloadData: function(){
      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      const data = this.getAxiosData("download");
      const successCallback = function(rowData){
        
        toast.success("稍等下載觸發",appVariable.toastStyle);
        helper.downloadSuccessCallback(rowData);
        
      };
      const failureCallback = function(data){
        alert("操作失敗，請確認參數是否正確");
        this.table.isLoading = false
      }
      const errorCallback = function(response){
        alert("網路錯誤，請稍後再試");
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },
    getAxiosUrl: function(){
      let url = appOption.project.apiURL + '/admin/records';
      console.log(url);
      return url;
    },
    getAxiosData: function(action, offset, limit){
      let page = Math.floor(offset/limit) + 1;
      // console.log("getAxiosData page",page);
      this.table.page = page;

      return {
        per: limit,
        page:page,
        workshopSession: this.workshopSession,
        workshopName: this.workshopName,
        workshopUsername: this.workshopUsername,
        memberNum: this.memberNum,
        memberName: this.memberName,
        memberContactName: this.memberContactName,
        memberArea: this.memberArea,
        scoutName: this.scoutName,
        scoutNum: this.scoutNum,
        startTime: this.startTime,
        endTime: this.endTime,
        isSignIn: this.isSignIn,
        isSignOut: this.isSignOut,
        action:action,
      };
    },
    refreshData: function(){
      console.log("refreshData");

      const offset = 0;
      const limit = this.table.pageOptions[0].value;
      const order = "id";
      const sort = "desc";
      this.doSearch(offset, limit, order, sort);
    },
    doSearch: function(offset, limit, order, sort){
      // https://vue3-lite-table.vercel.app/api-reference/props.html

      this.table.isLoading = true;

      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      let data = this.getAxiosData("list", offset, limit);
      data.order = order;
      data.sort = sort;
      console.log(data);
      const successCallback = function(rowData){
        
        let rows = [];
        // rowData.data.count
        // rowData.data.total
        // rowData.data.start
        for (const [key,obj] of Object.entries(rowData.data.result)) {
          rows.push(obj);
        };

        console.log(rows);

        let response = {
          rows: rows,
          count: rowData.data.count,
        }
        
        // refresh table rows
        vueScope.table.rows = response.rows;
        vueScope.table.totalRecordCount = rowData.data.total;
        vueScope.table.sortable.order = order;
        vueScope.table.sortable.sort = sort;
        vueScope.table.isLoading = false
      };
      const failureCallback = function(data){
        toast.error("操作失敗，請確認參數是否正確",appVariable.toastStyle);
        vueScope.table.isLoading = false
      }
      const errorCallback = function(response){
        toast.error("網路錯誤，請稍後再試",appVariable.toastStyle);
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },downloadDemo: function(){
      // 下載模版，提供匯入的編輯資料
      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      const data = {action:"downloadDemo"};
      const successCallback = function(rowData){
        
        toast.success("稍等下載觸發",appVariable.toastStyle);
        helper.downloadSuccessCallback(rowData);
      };
      const failureCallback = function(data){
        alert("操作失敗，請確認參數是否正確");
        this.table.isLoading = false
      }
      const errorCallback = function(response){
        alert("網路錯誤，請稍後再試");
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },
    uploadData: function(){

      if(!confirm("此操作僅做添加資料用途，如果已有資料則不會覆蓋，是否操作？")){
        return;
      }

      // 下載搜尋條件   
      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      const data = {action:"uploadData"};
      const fileInput = document.querySelector('#file');
      if(fileInput.files.length == 0 ){
        alert('請選擇檔案');
        return;
      }
      const file = fileInput.files[0];


      const successCallback = function(rowData){
         vueScope.refreshData();
      };
      const failureCallback = function(data){
        alert("操作失敗，請確認參數是否正確");
        this.table.isLoading = false
      }
      const errorCallback = function(response){
        alert("網路錯誤，請稍後再試");
      }
      helper.uploadFile(axios, url, loginToken, data, file, successCallback, failureCallback, errorCallback);

    }
  }
}
</script>
<template>
  <!-- BEGIN container -->
  <div class="container">
    <!-- BEGIN row -->
    <div class="row justify-content-center">
      <!-- BEGIN col-10 -->
      <div class="col-xl-10">
        <!-- BEGIN row -->
        <div class="row">
          <!-- BEGIN col-12 -->
          <div class="col-xl-12">
            <ul class="breadcrumb">
              <li class="breadcrumb-item active"><a href="#">簽到簽出清單</a></li>
            </ul>
          
            <h1 class="page-header">
              簽到簽出記錄 <small>不處理分頁，請注意大量搜尋</small>
            </h1>
          
            <hr class="mb-4" />

            <div class="mb-5">
              <card>
                <card-header class="fw-bold">

                  <ul class="nav nav-tabs pt-3 ps-4 pe-4">
                    <li class="nav-item me-1"><a href="#search" class="nav-link active" data-bs-toggle="tab">搜尋</a></li>
                    <li class="nav-item me-1"><a href="#edit" class="nav-link" data-bs-toggle="tab">編輯</a></li>
                    <li class="nav-item me-1"><a href="#add" class="nav-link" data-bs-toggle="tab">添加</a></li>
                  </ul>
                  <div class="tab-content p-4">
                    <div class="tab-pane fade show active" id="search">

                      <div class="mb-3 d-flex align-items-center row">
                    
                      <div class="align-items-center col-3">
                        <label class="pe-3">工作坊編號</label>
                        <input type="text" v-model="workshopUsername" class="form-control mb-3 mt-3" placeholder="數字六碼"  maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                      </div>
                      
                      <div class="align-items-center col-3">
                        <label class="pe-3">場次編號</label>
                        <input type="text" v-model="workshopSession" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                      </div>

                      <div class="align-items-center col-3">
                        <label class="pe-3">工作坊名稱</label>
                        <input type="text" v-model="workshopName" class="form-control mb-3 mt-3" placeholder="" />
                      </div>
                    
                      <div class="align-items-center col-3">
                        <label class="pe-3">學員編號</label>
                        <input type="text" v-model="memberNum" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                      </div>
                      <div class="align-items-center col-3">
                        <label class="pe-3">學員名稱</label>
                        <input type="text" v-model="memberName" class="form-control mb-3 mt-3" placeholder="" />
                      </div>
                      <div class="align-items-center col-3">
                        <label class="pe-3">緊急聯絡人</label>
                        <input type="text" v-model="memberContactName" class="form-control mb-3 mt-3" placeholder="" />
                      </div>
                      <div class="align-items-center col-3">
                        <label class="pe-3">分營區名稱</label>
                        <input type="text" v-model="memberArea" class="form-control mb-3 mt-3" placeholder="" />
                      </div>
                      <div class="col-3 align-items-center">
                        <label class="pe-3">團名</label>
                        <input type="text" v-model="scoutName" class="form-control mb-3 mt-3" placeholder="" />
                      </div>
                      <div class="col-3 align-items-center">
                        <label class="pe-3">團次</label>
                        <input type="text" v-model="scoutNum" class="form-control mb-3 mt-3" placeholder="地區+團次號碼" />
                      </div>

                      <div class="col-4 align-items-center">
                        <label class="pe-3">場次開始時間</label>
                        <input type="datetime-local" v-model="startTime" class="form-control mb-3 mt-3" />
                      </div>

                      <div class="col-4 align-items-center">
                        <label class="pe-3">場次結束時間</label>
                        <input type="datetime-local" v-model="endTime" class="form-control mb-3 mt-3" />
                      </div>
                      <div class="col-4 align-items-center">
                        <div class="form-check form-check-inline">
                          <label class="form-check-label"><input class="form-check-input" type="checkbox" v-model="isSignIn" />
                          已簽到</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <label class="form-check-label"><input class="form-check-input" type="checkbox" v-model="isSignOut" />
                          已簽出</label>
                        </div>
                      </div>
                      <div class="col-2 align-items-center">
                         <label class="pe-3 opacity-0"> This is Jerry　</label>
                        <input type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3 col-10" v-on:click="refreshData" value="搜尋" />
                      </div>

                      <div class="col-2 align-items-center">
                        
                        <template v-if="table.rows.length > 0">
                            <label class="pe-3 opacity-0"> This is Jerry　</label>
                          <input type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3 col-10" v-on:click="downloadData" value="下載" />
                        </template>
                      </div>

                      <div class="col-3 align-items-center">
                         <label class="pe-3 opacity-0"> This is Jerry　</label>
                        <input type="button" class="btn btn-outline-info fw-500 mb-3 mt-3 col-10" v-on:click="sync" value="手動同步場次" />
                      </div>
                    </div>

                    </div> <!-- .tab-pane#search END -->
                    <div class="tab-pane fade" id="edit">
                      <div class="mb-3 d-flex align-items-center row">

                        <div class="align-items-center col-2">
                          <label class="pe-3">ID</label>
                          <input type="text" data-id="id" class="form-control mb-3 mt-3" placeholder="請先搜尋" readonly="readonly" />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">工作坊編號</label>
                          <input type="text" data-id="workshopUsername" class="form-control mb-3 mt-3" placeholder="請先搜尋" />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">場次編號</label>
                          <input type="text" data-id="workshopSession" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">學員編號</label>
                          <input type="text" data-id="memberNum" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="col-4 align-items-center">
                          <label class="pe-3">簽到時間</label>
                          <input type="datetime-local" data-id="signIn" class="form-control mb-3 mt-3" />
                        </div>

                        <div class="col-4 align-items-center">
                          <label class="pe-3">簽離時間</label>
                          <input type="datetime-local" data-id="signOut" class="form-control mb-3 mt-3" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3"><small>點擊列表內容進行編輯</small></label>
                          <input type="button" class="btn btn-outline-success fw-500 mb-3 mt-3 col-10"  data-id="name" v-on:click="modifyData('edit')" value="修改" />
                        </div>
                      </div>
                    </div> <!-- .tab-pane#edit END -->
                    <div class="tab-pane fade" id="add">
                      <div class="mb-3 d-flex align-items-center row">

                        <div class="align-items-center col-3">
                          <label class="pe-3">工作坊編號</label>
                          <input type="text" data-id="workshopUsername" class="form-control mb-3 mt-3" placeholder="請先搜尋" />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">場次編號</label>
                          <input type="text" data-id="workshopSession" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">學員編號</label>
                          <input type="text" data-id="memberNum" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="col-4 align-items-center">
                          <label class="pe-3">簽到時間</label>
                          <input type="datetime-local" data-id="signIn" class="form-control mb-3 mt-3" />
                        </div>

                        <div class="col-4 align-items-center">
                          <label class="pe-3">簽離時間</label>
                          <input type="datetime-local" data-id="signOut" class="form-control mb-3 mt-3" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3 opacity-0">This is Jerry called</label>
                          <input type="button" class="btn btn-outline-success fw-500 mb-3 mt-3 col-10" data-id="name" v-on:click="modifyData('add')"  value="添加" />
                        </div>
                      </div>

                      <div class="mb-3 d-flex align-items-center row">

                        <div class="align-items-center col-4">
                          <label class="pe-3">上傳添加資料</label>
                          <input type="file" class="form-control mb-3 mt-3" placeholder="" id="file"/>
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3 opacity-0">This is Jerry</label>
                          <input type="button" class="btn btn-outline-warning fw-500 mb-3 mt-3 col-12" data-id="name" v-on:click="uploadData" value="點擊送出上傳" />
                        </div>
                        <div class="col-2 align-items-center">
                          <label class="pe-3 opacity-0">This is Jerry</label>
                          <input type="button" class="btn btn-outline-info fw-500 mb-3 mt-3 col-12" data-id="name" v-on:click="downloadDemo" value="下載匯入檔" />
                        </div>
                      </div>

                    </div> <!-- .tab-pane#edit END -->
                  </div> <!-- .tab-content END -->

                  
                </card-header>
              
                <card-body>
                  <div>
                   
                      <vue-table-lite class="vue-table" id="table-element" :is-loading="table.isLoading" 
                      :is-re-search="table.isReSearch"
                      :columns="table.columns"
                      :rows="table.rows"
                      :total="table.totalRecordCount"
                      :sortable="table.sortable"
                      :rowClasses="table.rowClasses"
                      :is-static-mode="false"
                      :is-hide-paging="false"
                      :messages="table.messages"
                      :page="table.page"
                      :page-options="table.pageOptions"
                      @is-finished="table.isLoading = false"
                      @do-search="doSearch"
                      @row-clicked="rowClicked"
                    />

                  </div>
                </card-body>
              </card>
            </div>

          </div>
          <!-- END col-12-->
          
        </div>
        <!-- END row -->
      </div>
      <!-- END col-10 -->
    </div>
    <!-- END row -->
  </div>
  <!-- END container -->
</template>
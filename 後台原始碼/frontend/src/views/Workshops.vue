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
          headerStyles:{minWidth:"60px"},
          sortable: true,
          isKey: true,
        },
        {
          label: "編號",
          field: "workshop_username",
          width: "10%",
          headerStyles:{minWidth:"140px"},
          sortable: true,
        },
        {
          label: "名稱",
          field: "workshop_name",
          width: "30%",
          headerStyles:{minWidth:"150px"},
          sortable: true,
        },
        {
          label: "活動中心",
          field: "workshop_area",
          width: "30%",
          headerStyles:{minWidth:"150px"},
          sortable: true,
        },
        {
          label: "可用場次",
          field: "workshop_sessions",
          width: "30%",
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
      }
    });

    // login
    helper.checkLogedIn(axios, this.$router, appOption, appVariable);
    
    return {
      table,
      id:"",
      workshopName:"",
      workshopSessions:"",
      workshopUsername:"",
      workshopArea:"",
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

    

    let config = localStorage.getItem(appVariable.key.accountConfig);
    config = JSON.parse(config);
    
    this.defalutSessions = config.allSessions;

    for (var i = 0; i < this.defalutSessions.length; i++) {
      var item = '<div class="form-check form-check-inline">'+'<label class="form-check-label" for="option-add-'+this.defalutSessions[i]+'">'+'<input class="form-check-input checkbox add" type="checkbox" value="'+this.defalutSessions[i]+'" id="option-add-'+this.defalutSessions[i]+'"/>'+this.defalutSessions[i]+'</label></div>'

     $('#checkboxAdd').append(item);
    }

    for (var i = 0; i < this.defalutSessions.length; i++) {
      var item = '<div class="form-check form-check-inline">'+'<label class="form-check-label" for="option-edit-'+this.defalutSessions[i]+'">'+'<input class="form-check-input checkbox edit" type="checkbox" value="'+this.defalutSessions[i]+'" id="option-edit-'+this.defalutSessions[i]+'">'+this.defalutSessions[i]+'</label></div>'
 
       $('#checkboxEdit').append(item);
    }
  
    this.refreshData();
  },
  beforeUnmount() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';
  },
  methods: {

    rowClicked: function(row){
      
      let sessionsStr = "";
      for (const [key, value] of Object.entries(row)) {
          let newKey = namingStyle.camel(key);
          if($('#edit input[data-id="'+newKey+'"]').length > 0 ){
            console.log(key,value);
            $('#edit input[data-id="'+newKey+'"]').val(value);
          }

          if("workshop_sessions" == key){
            sessionsStr = value;
          }
      }

      // 處理 checkbox
      let sessionsArr = sessionsStr.split(",");
      $('.checkbox.edit').each(function(){
        if(sessionsArr.includes($(this).val())){
          $(this).prop('checked',true);
        }else{
          $(this).prop('checked',false);
        }
      });

      $('.checkbox.add').each(function(){
        if(sessionsArr.includes($(this).val())){
          $(this).prop('checked',true);
        }else{
          $(this).prop('checked',false);
        }
      });

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
     
      let checkboxValArr =[];
      $('.checkbox.'+action+':checked').each(function(){
        checkboxValArr.push($(this).val());
      });

      $('.checkboxArea.'+action+' input[data-id="workshopSessions"]').val(checkboxValArr.toString());
      
      console.log( $('.checkboxArea.'+action+' input[data-id="workshopSessions"]').val());

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

      let scope = this;
      const successCallback = function(rowData){
        
        console.log(rowData);
        toast.success("操作成功，請重新搜尋",appVariable.toastStyle);
        scope.refreshData();
        
      };
      const failureCallback = function(data){
        // console.log("failureCallback",data);
        alert("操作失敗，"+data.message);
        scope.table.isLoading = false
      }
      const errorCallback = function(response){
        alert("網路錯誤，請稍後再試");
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },
    downloadDemo: function(){
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

      if(!confirm("此操作將會先清空資料表，確認是否執行？")){
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

    },
    downloadData: function(){
      // 下載搜尋條件
      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      let data = this.getAxiosData("download");
      
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
      let url = appOption.project.apiURL + '/admin/workshops';
      console.log(url);
      return url;
    },
    getAxiosData: function(action){
      return {
        workshopSessions: this.workshopSessions,
        workshopName: this.workshopName,
        workshopUsername: this.workshopUsername,
        workshopArea: this.workshopArea,
        action:action,
      };
    },
    refreshData: function(){
      console.log("refreshData");

      const offset = 0;
      const limit = 100000;
      const order = "id";
      const sort="asc";
      this.doSearch(offset, limit, order, sort);
    },
    doSearch: function(offset, limit, order, sort){
      // https://vue3-lite-table.vercel.app/api-reference/props.html

      this.table.isLoading = true;


      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      const data = this.getAxiosData("list");
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

        let response = {
          rows: rows,
          count: rowData.data.count,
        }

        // console.log("response.rows",response.rows);
        
        // refresh table rows
        vueScope.table.rows = response.rows;
        vueScope.table.totalRecordCount = response.count;
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
              <li class="breadcrumb-item active"><a href="#">工作坊清單</a></li>
            </ul>
          
            <h1 class="page-header">
              修改工作坊 <small>工作坊資訊列表</small>
            </h1>
          
            <hr class="mb-4" />

            <div class="mb-5">
              <card>
                <card-header class="fw-bold ">
                  <ul class="nav nav-tabs pt-3 ps-4 pe-4">
                    <li class="nav-item me-1"><a href="#search" class="nav-link active" data-bs-toggle="tab">搜尋</a></li>
                    <li class="nav-item me-1"><a href="#edit" class="nav-link" data-bs-toggle="tab">編輯</a></li>
                    <li class="nav-item me-1"><a href="#add" class="nav-link" data-bs-toggle="tab">添加</a></li>
                  </ul>
                  <div class="tab-content p-4">
                    <div class="tab-pane fade show active" id="search">
                      <div class="mb-3 d-flex align-items-center row">
                        <div class="align-items-center col-2">
                          <label class="pe-3">ID</label>
                          <input type="text" v-model="id" class="form-control mb-3 mt-3" placeholder="數字"  maxlength="15"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">工作坊編號</label>
                          <input type="text" v-model="workshopUsername" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-4">
                          <label class="pe-3">工作坊名稱</label>
                          <input type="text" v-model="workshopName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">活動中心</label>
                          <input type="text" v-model="workshopArea" class="form-control mb-3 mt-3" maxlength="64"  />
                        </div>
                        
                        <div class="align-items-center col-4">
                          <label class="pe-3">場次編號</label>

                          <input type="text" v-model="workshopSessions" class="form-control mb-3 mt-3" placeholder="" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9,]/,'');}).call(this)"  />
                        </div>
                        <div class="col-2 align-items-center">
                          <label class="pe-3 opacity-0">12　</label>
                          <input type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3 col-12" v-on:click="refreshData" value="搜尋" />
                        </div>
                        <div class="col-2 align-items-center">
                        
                        <template v-if="table.rows.length > 0">
                            <label class="pe-3 opacity-0"> This is Jerry　</label>
                          <input type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3 col-10" v-on:click="downloadData" value="下載" />

                        </template>
                      </div>
                      </div> 
                    </div> <!-- .tab-pane#search END -->
                    <div class="tab-pane fade" id="edit">
                      <div class="d-flex align-items-center row">
                        <div class="align-items-center col-2">
                          <label class="pe-3">ID</label>
                          <input type="text" data-id="id" class="form-control mb-3 mt-3" placeholder="請先搜尋" readonly="readonly" />
                        </div>
                        <div class="align-items-center col-2">
                          <label class="pe-3">工作坊編號</label>
                          <input type="text" data-id="workshopUsername" class="form-control mb-3 mt-3" placeholder="數字六碼" readonly="readonly" />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">工作坊名稱</label>
                          <input type="text" data-id="workshopName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">活動中心</label>
                          <input type="text" data-id="workshopArea" class="form-control mb-3 mt-3" placeholder="" maxlength="64" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">密碼</label>
                          <input type="text" data-id="password" class="form-control mb-3 mt-3" placeholder="留空不更動" />
                        </div>
                        
                        <div class="align-items-center col-12">
                          <label class="pe-3">可用場次編號</label>

                          <div id="checkboxEdit" class="edit checkboxArea mt-3">


                          <input type="hidden" data-id="workshopSessions" class="form-control mb-3 mt-3" placeholder="數字六碼，英數半形逗號分隔" required="required" />
                          </div>
                          
                        </div> 
                        
                        <div class="col-3 mt-3 align-items-center">
                          <label class="pe-3"><small>點擊列表內容進行編輯</small></label>
                          <input type="button" class="btn btn-outline-success fw-500 mb-3 mt-3 col-12" data-id="name" v-on:click="modifyData('edit')" value="編輯" />
                        </div>
                      </div>
                    </div> <!-- .tab-pane#edit END -->
                    <div class="tab-pane fade" id="add">
                      <div class="mb-3 d-flex align-items-center row">
                        
                        <div class="align-items-center col-3">
                          <label class="pe-3">工作坊編號</label>
                            <input type="text" data-id="workshopUsername" class="form-control mb-3 mt-3" placeholder="數字六碼"  maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">工作坊名稱</label>
                          <input type="text" data-id="workshopName" class="form-control mb-3 mt-3" placeholder="" maxlength="64" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">活動中心</label>
                          <input type="text" data-id="workshopArea" class="form-control mb-3 mt-3" placeholder="" maxlength="64" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">密碼</label>
                          <input type="text" data-id="password" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        
                        <div class="align-items-center col-12">
                          <label class="pe-3">可用場次編號</label>
                          
                          <div id="checkboxAdd" class="add checkboxArea mt-3">

                            <input type="hidden" data-id="workshopSessions" class="form-control mb-3 mt-3" placeholder="數字六碼，英數半形逗號分隔" required="required" />
                          </div>
                          
                        </div> 
                        
                        <div class="col-2 align-items-center">
                          <label class="pe-3 opacity-0">This is Jerry</label>
                          <input type="button" class="btn btn-outline-success fw-500 mb-3 mt-3 col-12" data-id="name" v-on:click="modifyData('add')" value="添加" />
                        </div>
                        
                      </div>
                      <div class="mb-3 d-flex align-items-center row">

                        <div class="align-items-center col-4">
                          <label class="pe-3">上傳覆蓋資料</label>
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
                      <vue-table-lite class="vue-table" id="table-element" :is-loading="table.isLoading" :is-re-search="true"
                        :columns="table.columns"
                      :rows="table.rows"
                      :total="table.totalRecordCount"
                      :sortable="table.sortable"
                      :rowClasses="table.rowClasses"
                      :is-static-mode="false"
                      :is-hide-paging="true"
                      :messages="table.messages"
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
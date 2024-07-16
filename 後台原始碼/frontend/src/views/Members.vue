<script>
import { defineComponent, reactive, computed } from 'vue';

// import vueTable from '@/components/plugins/VueTable.vue';
import VueTableLite from "vue3-table-lite/ts";
import datepicker from '@/components/plugins/Datepicker.vue'

import axios from 'axios';
import { useAppVariableStore } from '@/stores/app-variable';

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
  data() {
    
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
          sortable: true,
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
        { value: 50, text: 50 },
        { value: 100, text: 100},
        { value: 500, text: 500},
        { value: 1000, text: 1000}
      ]
    });


    // login
    helper.checkLogedIn(axios, this.$router, appOption, appVariable);
    
    return {
      table,
      id:"",
      sessionCount:"",
      workshopUsername:"",
      memberArea:"",
      scoutName:"",
      scoutNum:"",
      memberNum:"",
      memberName:"",

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
      let url = appOption.project.apiURL + '/admin/members';
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
        isAbove: $('#isAbove:checked').val() == "isAbove",
        onlyNonJoin: $('#onlyNonJoin:checked').val() == "onlyNonJoin",
        workshopUsername: this.workshopUsername,
        sessionCount: this.sessionCount,
        memberArea: this.memberArea,
        scoutName: this.scoutName,
        scoutNum: this.scoutNum,
        memberNum: this.memberNum,
        memberName: this.memberName,
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
    getNowPage: function(pageNo){
      console.log("getNowPage",pageNo);

    },
    doSearch: function(offset, limit, order, sort){
      // https://vue3-lite-table.vercel.app/api-reference/props.html
      // console.log(offset);
      // console.log(limit);
      console.log("Members",order);
      console.log("Members",sort);

      this.table.isLoading = true;
 
      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = this.getAxiosUrl();
      let data = this.getAxiosData("list", offset, limit);
      data.order = order;
      data.sort = sort;
      console.log("Members",data);
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
        // let page = Math.floor(rowData.data.start / rowData.data.count) + 1;
        // console.log("doSearch page",page);
        
        // refresh table rows
        vueScope.table.rows = response.rows;
        vueScope.table.totalRecordCount = rowData.data.total;
        vueScope.table.sortable.order = order;
        vueScope.table.sortable.sort = sort;
        vueScope.table.isLoading = false;
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
              <li class="breadcrumb-item active"><a href="#">學員清單</a></li>
            </ul>
          
            <h1 class="page-header">
              學員清單 <small>學員名單條件搜尋、編輯、匯出 搜尋無法分頁，請注意資料取得數量</small>
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
                          <label class="pe-3">已完成場次數量</label>
                          <input type="number" v-model="sessionCount" class="form-control mb-3 mt-3" placeholder="數字為空或 -1 表示略過該條件" min="-1" step="1" max="99"  maxlength="2" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">參與過的工作坊編號</label>
                          <input type="text" v-model="workshopUsername" class="form-control mb-3 mt-3" placeholder="數字六碼"  maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
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

                        <div class="align-items-center col-3">
                          <label class="pe-3">學員編號</label>
                          <input type="text" v-model="memberNum" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">學員名稱</label>
                          <input type="text" v-model="memberName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>

                        <div class="align-items-center col-3">
                           <!-- <label class="pe-3">特殊條件</label> -->
                          <div class="form-check form-check-inline"><label class="form-check-label" for="isAbove"><input class="form-check-input checkbox edit" type="checkbox" value="isAbove" id="isAbove" >含以上場次數量</label></div>
                          <div class="form-check form-check-inline"><label class="form-check-label" for="onlyNonJoin"><input class="form-check-input checkbox edit" type="checkbox" value="onlyNonJoin" id="onlyNonJoin">僅顯示從未參加的學員（忽略場次數量條件）</label></div>
                        </div>
                        
                        <div class="col-3 align-items-center">
                          <label class="pe-3 opacity-0"> This is Jerry　</label>
                          <input type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3 col-10" v-on:click="refreshData" value="搜尋" />
                        </div>

                        <div class="col-3 align-items-center">
                          <template v-if="table.rows.length > 0">
                            <label class="pe-3 opacity-0"> This is Jerry　</label>
                          <input type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3 col-10" v-on:click="downloadData" value="下載" />
                          </template>
                        </div>
                      </div> <!-- .row END  -->
                    </div> <!-- .tab-pane#search END -->
                    <div class="tab-pane fade" id="edit">
                      <div class="mb-3 d-flex align-items-center row">
                        <div class="align-items-center col-2">
                          <label class="pe-3">ID</label>
                          <input type="text" data-id="id" class="form-control mb-3 mt-3" placeholder="請先搜尋" readonly="readonly" required="required" />
                        </div>

                        <div class="align-items-center col-3">
                          <label class="pe-3">學員編號</label>
                          <input type="text" data-id="memberNum" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">學員名稱</label>
                          <input type="text" data-id="memberName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">分營區名稱</label>
                          <input type="text" data-id="memberArea" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">團名</label>
                          <input type="text" data-id="scoutName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">團次</label>
                          <input type="text" data-id="scoutNum" class="form-control mb-3 mt-3" placeholder="地區+團次號碼" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">聯絡電話</label>
                          <input type="text" data-id="memberPhone" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">緊急聯絡人</label>
                          <input type="text" data-id="memberContactName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">緊急聯絡電話</label>
                          <input type="text" data-id="memberContactPhone" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        
                        <div class="col-3 align-items-center">
                          <label class="pe-3"><small>點擊列表內容進行編輯</small></label>
                          <input type="button" class="btn btn-outline-success fw-500 mb-3 mt-3 col-10" data-id="name" v-on:click="modifyData('edit')" value="修改" />
                        </div>
                      </div> <!-- .row END -->
                    </div> <!-- .tab-pane#edit END -->
                    <div class="tab-pane fade" id="add">
                      <div class="mb-3 d-flex align-items-center row">
                        <div class="align-items-center col-3">
                          <label class="pe-3">學員編號</label>
                          <input type="text" data-id="memberNum" class="form-control mb-3 mt-3" placeholder="數字六碼" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"  />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">學員名稱</label>
                          <input type="text" data-id="memberName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="align-items-center col-3">
                          <label class="pe-3">分營區名稱</label>
                          <input type="text" data-id="memberArea" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">團名</label>
                          <input type="text" data-id="scoutName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">團次</label>
                          <input type="text" data-id="scoutNum" class="form-control mb-3 mt-3" placeholder="地區+團次號碼" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">聯絡電話</label>
                          <input type="text" data-id="memberPhone" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">緊急聯絡人</label>
                          <input type="text" data-id="memberContactName" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        <div class="col-3 align-items-center">
                          <label class="pe-3">緊急聯絡電話</label>
                          <input type="text" data-id="memberContactPhone" class="form-control mb-3 mt-3" placeholder="" />
                        </div>
                        
                        <div class="col-3 align-items-center">
                          <label class="pe-3 opacity-0"> This is Jerry　</label>
                          <input type="button" class="btn btn-outline-success fw-500 mb-3 mt-3 col-10" data-id="name" v-on:click="modifyData('add')" value="添加" />
                        </div>
                      </div> <!-- .row END -->
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
                      @get-now-page="getNowPage"
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
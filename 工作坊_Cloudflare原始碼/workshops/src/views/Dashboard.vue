<script>
import { defineComponent, reactive, computed } from 'vue';
// import vueTable from '@/components/plugins/VueTable.vue';
import VueTableLite from "vue3-table-lite/ts";
import axios from 'axios';
import { useAppVariableStore } from '@/stores/app-variable';
const appVariable = useAppVariableStore();

import { useAppOptionStore } from '@/stores/app-option';
const appOption = useAppOptionStore();

import * as helper from '@/composables/helper.js';


export default {
  setup () {
    
    // Table config
    const table = reactive({
      isLoading: false,
      columns: [
        {
          label: "#",
          field: "num",
          width: "10%",
          sortable: true,
          isKey: true,
        },
        {
          label: "場次 ID",
          field: "shopId",
          width: "10%",
          sortable: true,
          isKey: true,
        },
        {
          label: "學員 ID",
          field: "memberId",
          width: "10%",
          sortable: true,
        },
        {
          label: "簽到",
          field: "isSignin",
          width: "15%",
          sortable: true,
        },
        {
          label: "簽出",
          field: "isSignOut",
          width: "15%",
          sortable: true,
        },
      ],
      rows: [],
      totalRecordCount: 0,
      sortable: {
        order: "id",
        sort: "asc",
      },
    });
    
    return {
      table,
      // doSearch,
      searchTerm:""
    }
  },
  components:{
    VueTableLite
  },
  mounted() {
    appOption.appSidebarHide = false;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';

    // login
    helper.checkLogedIn(axios, this.$router, appOption, appVariable);

    let search = localStorage.getItem(appVariable.key.searchKey);
    if(null == search){
      search = "";
    }
    this.searchTerm = search;
    document.getElementById("searchTerm").value = this.searchTerm;
  
  },
  beforeUnmount() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';
  },
  methods: {
    refreshData: function(){
      // console.log("refreshData");

      const offset = 0;
      const limit = 1000;
      const order = "id";
      const sort="asc";
      this.doSearch(offset, limit, order, sort);
    },
    doSearch: function(offset, limit, order, sort){
      // https://vue3-lite-table.vercel.app/api-reference/props.html
      console.log("searchTerm "+this.searchTerm);

      this.table.isLoading = true;
      
      let loginToken = localStorage.getItem(appVariable.key.accountSession);
      if(null == loginToken){
        alert("尚未登入");
        this.$router.push('/login');
        return;
      }else if("" == this.searchTerm){
        alert("請輸入場次代碼");
        return;
      }


      localStorage.setItem(appVariable.key.searchKey, this.searchTerm);

      axios.post(appOption.project.apiURL + '/', {
        token: loginToken,
        search: this.searchTerm,
        action:"list"
      }).then((response) => {
        
        let rowData = response.data.result.data;
        if(response.data.result.checkSuccess){

          let rows = [];
          for (const [shopId, menberObj] of Object.entries(rowData)) {
            let count = 1;
            for (const [memberId, signObj] of Object.entries(menberObj)) {
              console.log("signObj",signObj);
              let obj = {
                num: 1,
                memberId: 0,
                shopId: shopId,
                isSignin: false,
                isSignOut: false,
              };
              obj.num = count;
              count ++;
              obj.memberId = memberId;
              obj.isSignin = signObj.inTime > 0 ? "是":"否";
              obj.isSignOut = signObj.outTime > 0 ? "是":"否";
              
              rows.push(obj);
            };
          };

          let response = {
            rows: rows,
            count: rows.length,
          }
          
          // refresh table rows
          this.table.rows = response.rows;
          this.table.totalRecordCount = response.count;
          this.table.sortable.order = order;
          this.table.sortable.sort = sort;

        }else{
          alert(response.data.result.errorMessage);
        }
        this.table.isLoading = false
      });
      
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
              <li class="breadcrumb-item active"><a href="#">資料清單</a></li>
            </ul>
          
            <h1 class="page-header">
              簽到/簽出資料清單 <small>正確資料請以營本部為主</small>
            </h1>
          
            <hr class="mb-4" />
          
            <!-- BEGIN #vue3TableLite -->
            <div id="vue3TableLite" class="mb-5">
              <card>
                <card-body>
              
                  <div class="mb-3 d-flex align-items-center row">
                    <div class="col-8 d-flex align-items-center">
                      <label class="pe-3">Search:</label>
                      <input v-model="searchTerm" id="searchTerm" class="form-control mb-3 mt-3" placeholder="請填入場次代碼" />
                    </div>
                    <div class="col-3">
                      <button type="button" class="btn btn-outline-theme fw-500 mb-3 mt-3" v-on:click="refreshData">刷新</button>
                    </div>
                    
                  </div>
                  <template v-if="!table.isLoading">
                    <vue-table-lite class="vue-table"  :is-loading="table.isLoading" :is-re-search="true"
                      :columns="table.columns"
                      :rows="table.rows"
                      :total="table.totalRecordCount"
                      :sortable="table.sortable"
                      :messages="table.messages"
                      :is-static-mode="false"
                      :is-hide-paging="true"
                      @is-finished="table.isLoading = false"
                  />
                </template>
                
                </card-body>
              </card>
            </div>
            <!-- END #vue3TableLite -->
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

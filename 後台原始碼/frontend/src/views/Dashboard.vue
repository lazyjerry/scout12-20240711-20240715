<script>
import { useAppVariableStore } from '@/stores/app-variable';
import apexchart from '@/components/plugins/Apexcharts.vue';

const appVariable = useAppVariableStore();

import axios from 'axios';

import { useAppOptionStore } from '@/stores/app-option';
const appOption = useAppOptionStore();

import * as helper from '@/composables/helper.js';


export default {
  components: {
    apexchart: apexchart
  },
  data() {

    // login
    helper.checkLogedIn(axios, this.$router, appOption, appVariable);

    console.log("data");
    return {
      renderComponent: false,
      stats:[],
    }
  },
  methods: {
    
    getStatsData() {

      this.renderComponent = false;
      console.log("getStatsData");

      let rows = [];
      let modes = [{
        height: 30,
        options: { chart: { type: 'bar', sparkline: { enabled: true } }, colors: [appVariable.color.theme], plotOptions: { bar: { horizontal: false, columnWidth: '65%',  endingShape: 'rounded' } } },
        series: [{ name: 'Visitors', data: [this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo()] }]
      }, {
        height: 30,
        options: { chart: { type: 'line', sparkline: { enabled: true } }, colors: [appVariable.color.theme], stroke: { curve: 'straight', width: 2 } },
        series: [{ name: 'Visitors', data: [this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo()] }]
      }, {
        height: 45,
        options: { chart: { type: 'pie', sparkline: { enabled: true } }, colors: ['rgba('+ appVariable.color.themeRgb + ', 1)', 'rgba('+ appVariable.color.themeRgb + ', .75)', 'rgba('+ appVariable.color.themeRgb + ', .5)'], stroke: { show: false } },
        series: [this.randomNo(), this.randomNo(), this.randomNo()]
      }, {
        height: 45,
        options: { chart: { type: 'donut', sparkline: { enabled: true } }, colors: ['rgba('+ appVariable.color.themeRgb + ', .15)', 'rgba('+ appVariable.color.themeRgb + ', .35)', 'rgba('+ appVariable.color.themeRgb + ', .55)', 'rgba('+ appVariable.color.themeRgb + ', .75)', 'rgba('+ appVariable.color.themeRgb + ', .95)'], stroke: { show: false, curve: 'smooth', lineCap: 'butt', colors: 'rgba(' + appVariable.color.blackRgb + ', .25)', width: 2, dashArray: 0 }, plotOptions: { pie: { donut: { background: 'transparent' } } } },
        series: [this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo(), this.randomNo()]
      }];

      const vueScope = this;
      const loginToken = helper.getToken(this.$router, appVariable);
      const url = appOption.project.apiURL + '/admin/getStatus';
      const data = {
      };
      const successCallback = function(rowData){
        
        rows = [];
        for (const [index,data] of Object.entries(rowData.data)) {
          
          let info = [];
          if('' != data.subtitle1){
            info.push({ icon: 'fa fa-chevron-up fa-fw me-1', text: data.subtitle1 });
          }
          if('' != data.subtitle2){
            info.push({ icon: 'far fa-chevron-down fa-fw me-1', text: data.subtitle2 });
          }
          if('' != data.subtitle3){
            info.push({ icon: 'far fa-times-circle fa-fw me-1', text: data.subtitle3 });
          }

          let obj = { 
            title: data.title, total: data.total, 
            info: info,
            chart: modes[Math.floor(Math.random()*10)%modes.length],
          };

          rows.push(obj)
        };
        vueScope.stats = rows;
        vueScope.renderComponent = true;

      };
      const failureCallback = function(data){
        alert("操作失敗，請確認參數是否正確");
      }
      const errorCallback = function(){
        alert("網路錯誤，請稍後再試");
      }
      helper.post(axios, url, loginToken, data, successCallback, failureCallback, errorCallback);
    },
    randomNo() {
      return Math.floor(Math.random() * 60) + 30
    },
    
  },
  mounted() {
    appOption.appSidebarHide = false;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';

    this.getStatsData();
  },
  beforeUnmount() {
    appOption.appSidebarHide = true;
    appOption.appHeaderHide = false;
    appOption.appContentClass = '';
  }
}
</script>
<template>
  <!-- BEGIN row -->
  <div class="row" v-if="renderComponent">
    <!-- BEGIN stats -->
    <div class="col-xl-3 col-lg-6" v-for="stat in stats">
      <!-- BEGIN card -->
      <card class="mb-3">
        <card-body>
          <div class="d-flex fw-bold small mb-3">
            <span class="flex-grow-1">{{ stat.title }}</span>
            <!-- <card-expand-toggler /> -->
          </div>
          <div class="row align-items-center mb-2">
            <div class="col-7">
              <h3 class="mb-0">{{ stat.total }}</h3>
            </div>
            <div class="col-5">
              <div class="mt-n3 mb-n2">
                <apexchart :height="stat.chart.height" :options="stat.chart.options" :series="stat.chart.series"></apexchart>
              </div>
            </div>
          </div>
          <div class="small text-inverse text-opacity-50 text-truncate">
            <template v-for="statInfo in stat.info">
              <div>
                <i v-bind:class="statInfo.icon"></i> {{ statInfo.text }}
              </div>
            </template>
          </div>
        </card-body>
      </card>
      <!-- END card -->
    </div>
    <!-- END stats -->
    
    
  </div>
  <!-- END row -->
</template>
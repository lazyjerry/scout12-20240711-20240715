<script>
import { useAppOptionStore } from '@/stores/app-option';
import { useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
const appOption = useAppOptionStore();

import { useAppVariableStore } from '@/stores/app-variable';
const appVariable = useAppVariableStore();

import * as helper from '@/composables/helper.js';

export default {
	data(){
		let username = "";
		let password = "";
		const remember = false;
		const data = appOption.project;

		if(appOption.project.isDev){
			username="admin";
			password="1qaz@WSX";
		}
		
		return {username, password, remember,loading:false, data};
	},
  watch:{

  },
	mounted() {
		appOption.appSidebarHide = true;
		appOption.appHeaderHide = true;
		appOption.appContentClass = 'p-0';
			// helper.test(axios, appOption);
	},
	beforeUnmount() {
		appOption.appSidebarHide = true;
		appOption.appHeaderHide = true;
		appOption.appContentClass = '';
	},
	methods: {
		// https://v1-cn.vuejs.org/guide/forms.html
		
		submitForm: function() {



			this.loading = true;

			// console.log(this.username+", "+this.password+", "+this.remember);
			// https://www.runoob.com/vue3/vue3-ajax-axios.html
			
			let url = appOption.project.apiURL + '/auth/login';
			let data = {
				username:this.username,
				password:this.password,
				remember:this.remember ? 1 : 0,
			};
			const vueScope = this;
			const successCallback = function(data){
				console.log("token",data.data.token);
				localStorage.setItem(appVariable.key.accountSession, data.data.token);
				console.log("config",data.data.config);
				localStorage.setItem(appVariable.key.accountConfig, JSON.stringify(data.data.config));
				vueScope.$router.push('/');
			};
			const failureCallback = function(data){
				alert("登入失敗，請檢查帳號密碼");
			}
			const errorCallback = function(response){
				alert("網路錯誤，請稍後再試");
			}
			helper.post(axios, url,"", data, successCallback, failureCallback, errorCallback);
			
		}
	}
}
</script>

<template>
	<!-- BEGIN login -->
	<div class="login">
		<!-- BEGIN login-content -->
		<div class="login-content">
			<form v-on:submit.prevent="submitForm()" method="POST" name="login_form">
				<h1 class="text-center">管理員登入</h1>
				<div class="text-inverse text-opacity-50 text-center mb-4">
					輸入帳號密碼登入
				</div>
				<div class="mb-3">
					<label class="form-label">帳號 <span class="text-danger">*</span></label>
					<input type="text" class="form-control form-control-lg bg-white bg-opacity-5"  placeholder="請輸入帳號(工作坊編號)" :disabled="loading" v-model="username" id="username" required="required"  />
				</div>
				<div class="mb-3">
					<div class="d-flex">
						<label class="form-label">密碼 <span class="text-danger">*</span></label>
					</div>
					<input type="password" class="form-control form-control-lg bg-white bg-opacity-5"  placeholder="" :disabled="loading" v-model="password" id="password" required="required"/>
				</div>
				<div class="mb-3">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" :disabled="loading" v-model="remember" id="remember" />
						<label class="form-check-label" for="remember">記住我</label>
					</div>
				</div>
				<button type="submit" class="btn btn-outline-theme btn-lg d-block w-100 fw-500 mb-3" :disabled="loading" >登入</button>
				
			</form>
		</div>
		<!-- END login-content -->
	</div>
	<!-- END login -->
</template>
<script>
import { useAppOptionStore } from '@/stores/app-option';
import { useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
const appOption = useAppOptionStore();

import { useAppVariableStore } from '@/stores/app-variable';
const appVariable = useAppVariableStore();


export default {
	setup(){
		let username = "";
		let password = "";
		const remember = false;
		const data = appOption.project;
		
		if(appOption.project.isDev){
			username="10006";
			password="10006";
		}
		return {username, password, remember,loading:false, data};
	},
	mounted() {
		appOption.appSidebarHide = true;
		appOption.appHeaderHide = true;
		appOption.appContentClass = 'p-0';
		return 
	},
	beforeUnmount() {
		appOption.appSidebarHide = false;
		appOption.appHeaderHide = false;
		appOption.appContentClass = '';
	},
	methods: {
		// https://v1-cn.vuejs.org/guide/forms.html
		
		submitForm: function() {

			this.loading = true;

			console.log(this.username+", "+this.password+", "+this.remember);

			// https://www.runoob.com/vue3/vue3-ajax-axios.html
			axios.post(appOption.project.apiURL + '/', {
				username:this.username,
				password:this.password,
				remember:this.remember ? 1 : 0,
				action:"login"
			}).then((response) => {
			  console.log(response.data.result);
				if(response.data.result.loginSuccess && "" != response.data.result.token){
			  	localStorage.setItem(appVariable.key.accountSession, response.data.result.token);
			  	localStorage.setItem(appVariable.key.workshopSessions, response.data.result.sessions);
			  	this.$router.push('/');
			  }else{
			  	alert("登入失敗，請檢查帳號密碼");
			  }
			});
			// 
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
				<h1 class="text-center">工作坊登入</h1>
				<div class="text-inverse text-opacity-50 text-center mb-4">
					一次請一台裝置登入
				</div>
				<div class="mb-3">
					<label class="form-label">工作坊編號 <span class="text-danger">*</span></label>
					<input type="text" class="form-control form-control-lg bg-white bg-opacity-5"  placeholder="請輸入帳號(工作坊編號)" :disabled="loading" v-model="username" id="username" required="required" maxlength="6"  onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)" />
				</div>
				<div class="mb-3">
					<div class="d-flex">
						<label class="form-label">密碼 <span class="text-danger">*</span></label>
						<!-- <a href="#" class="ms-auto text-inverse text-decoration-none text-opacity-50">Forgot password?</a> -->
					</div>
					<input type="password" class="form-control form-control-lg bg-white bg-opacity-5"  placeholder="" :disabled="loading" v-model="password" id="password" required="required"  maxlength="32"/>
				</div>
				<div class="mb-3">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" :disabled="loading" v-model="remember" id="remember" />
						<label class="form-check-label" for="remember">記住我</label>
					</div>
				</div>
				<button type="submit" class="btn btn-outline-theme btn-lg d-block w-100 fw-500 mb-3" :disabled="loading" >登入</button>
				<!-- <div class="text-center text-inverse text-opacity-50">還沒有帳號嗎？<RouterLink to="/register">註冊</RouterLink>.
				</div> -->
			</form>
		</div>
		<!-- END login-content -->
	</div>
	<!-- END login -->
</template>
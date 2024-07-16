<script>
import { useAppOptionStore } from '@/stores/app-option';
import { useRouter, RouterLink } from 'vue-router';
import axios from 'axios';
const appOption = useAppOptionStore();

export default {
	setup(){
		let name="";
		let username = "";
		let password = "";
		let cpassword = "";
		if(appOption.project.isDev){
			name = "John";
			username="111111";
			password="111111";
			cpassword="111111";
		}

		let loading = false;

		const data = appOption.project;
		return {name, username, password, cpassword, loading, data};
	},
	mounted() {
		appOption.appSidebarHide = true;
		appOption.appHeaderHide = true;
		appOption.appContentClass = 'p-0';
	},
	beforeUnmount() {
		appOption.appSidebarHide = true;
		appOption.appHeaderHide = true;
		appOption.appContentClass = 'p-0';
	},
	methods: {
		submitForm: function() {
			this.loading = true;
			if("" == this.password || this.password != this.cpassword){
				alert("確認密碼不相符，請重新輸入");

				this.password = this.cpassword = document.getElementById("password").value = document.getElementById("cpassword").value = "";
				this.loading = false;
			}else{
			// this.$router.push('/');
				axios.post(appOption.project.apiURL + '/',{
					name:this.name,
					username:this.username,
					password:this.password,
					sessions:"",
					action:"register"
					// action:"test"
				}).then((response) => {
				 	// alert(JSON.stringify(response.data));
				 	if(response.data.result.registerSuccess){
				 		alert("註冊成功！");
				 		this.$router.push('/login');
				 	}else{
				 		alert("註冊失敗，請重新再註冊一次");
				 		this.password = this.cpassword = document.getElementById("password").value = document.getElementById("cpassword").value = "";
				 	}
				 	this.loading = false;
				});
			}
		}
	}
}
</script>
<template>
	<!-- BEGIN register -->
	<div class="register">
		<!-- BEGIN register-content -->
		<div class="register-content">
			<form v-on:submit.prevent="submitForm()" method="POST" name="register_form">
				<h1 class="text-center">註冊</h1>
				<p class="text-inverse text-opacity-50 text-center">由此註冊帳號密碼</p>
				<div class="mb-3">
					<label class="form-label">工作坊名字 <span class="text-danger">*</span></label>
					<input type="text" class="form-control form-control-lg bg-white bg-opacity-5" placeholder="e.g John Smith"  :disabled="loading" v-model="name" id="name" required="required" maxlength="20"/>
				</div>
				<div class="mb-3">
					<label class="form-label">帳號(工作坊編號) <span class="text-danger">*</span></label>
					<input type="text" class="form-control form-control-lg bg-white bg-opacity-5" placeholder="123456" :disabled="loading" v-model="username" id="username" required="required" maxlength="6" onkeyup="(this.v=function(){this.value=this.value.replace(/[^0-9]/,'');}).call(this)"/>
				</div>
				<div class="mb-3">
					<label class="form-label">密碼 <span class="text-danger">*</span></label>
					<input type="password" class="form-control form-control-lg bg-white bg-opacity-5" :disabled="loading" v-model="password" id="password" required="required"/>
				</div>
				<div class="mb-3">
					<label class="form-label">確認密碼 <span class="text-danger">*</span></label>
					<input type="password" class="form-control form-control-lg bg-white bg-opacity-5" :disabled="loading" v-model="cpassword" id="cpassword" required="required"/>
				</div>
				<!-- 
				<div class="mb-3">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" value="" id="customCheck1" />
						<label class="form-check-label" for="customCheck1">I have read and agree to the <a href="#">Terms of Use</a> and <a href="#">Privacy Policy</a>.</label>
					</div>
				</div> -->
				<div class="mb-3">
					<button type="submit" class="btn btn-outline-theme btn-lg d-block w-100">註冊</button>
				</div>
				<div class="text-inverse text-opacity-50 text-center">已經有帳號嗎？ <RouterLink to="/login">點我登入</RouterLink>
				</div>
			</form>
		</div>
		<!-- END register-content -->
	</div>
	<!-- END register -->
</template>
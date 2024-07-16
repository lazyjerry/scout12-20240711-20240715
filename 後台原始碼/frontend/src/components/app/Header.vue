<script setup lang="ts">
import { slideToggle } from '@/composables/slideToggle.js';
import { useAppOptionStore } from '@/stores/app-option';
import { RouterLink, useRouter } from 'vue-router';

const appOption = useAppOptionStore();

function toggleAppSidebarCollapsed() {
	if (!appOption.appSidebarHide) {
		if (appOption.appSidebarCollapsed) {
			appOption.appSidebarToggled = !appOption.appSidebarToggled;
		} else if (appOption.appSidebarToggled) {
			appOption.appSidebarToggled = !appOption.appSidebarToggled;
		}
		appOption.appSidebarCollapsed = !appOption.appSidebarCollapsed;
	}
}
function toggleAppSidebarMobileToggled() {
	if (!(appOption.appTopNav && appOption.appSidebarHide)) {
		appOption.appSidebarMobileToggled = !appOption.appSidebarMobileToggled;
	} else {
		slideToggle(document.querySelector('.app-top-nav'));
		window.scrollTo(0, 0);
	}
}
function toggleAppHeaderSearch(event) {
	event.preventDefault();
	
	appOption.appHeaderSearchToggled = !appOption.appHeaderSearchToggled;
}
function checkForm(event) {
	event.preventDefault();
	this.$router.push({ path: '/' })
}

function logout(){
	event.preventDefault();
	localStorage.clear();
  location.href = "/login";
}
function openChronjob(){
	event.preventDefault();
	window.open("/cronjobs/", '_blank').focus();
}
</script>
<template>
	<div id="header" class="app-header">
		<!-- BEGIN desktop-toggler -->
		<div class="desktop-toggler">
			<button type="button" class="menu-toggler" v-on:click="toggleAppSidebarCollapsed">
				<span class="bar"></span>
				<span class="bar"></span>
				<span class="bar"></span>
			</button>
		</div>
		<!-- BEGIN desktop-toggler -->
		
		<!-- BEGIN mobile-toggler -->
		<div class="mobile-toggler">
			<button type="button" class="menu-toggler" v-on:click="toggleAppSidebarMobileToggled">
				<span class="bar"></span>
				<span class="bar"></span>
				<span class="bar"></span>
			</button>
		</div>
		<!-- END mobile-toggler -->
		
		<!-- BEGIN brand -->
		<div class="brand">
			<RouterLink to="/" class="brand-logo">
				<span class="brand-img">
					<span class="brand-img-text text-theme">簽</span>
				</span>
				<span class="brand-text">簽到系統後台</span>
			</RouterLink>
		</div>
		<!-- END brand -->
		
		<!-- BEGIN menu -->
		<div class="menu">
			
			<div class="menu-item" id="header-menu-msg">
			</div>
			<div class="menu-item" >
				<button type="button" class="btn btn-outline-warning btn-sm m-2" v-on:click="openChronjob">啟動擷取簽到資料</button>
			</div>
			<div class="menu-item dropdown dropdown-mobile-full">
				<a href="#" data-bs-toggle="dropdown" data-bs-display="static" class="menu-link">
					<div class="menu-img online">
						<img src="/assets/img/user/profile.jpg" alt="Profile" height="60">
					</div>
					<div class="menu-text d-sm-block d-none w-170px" id="username-title">尚未登入</div>
				</a>
				<div class="dropdown-menu dropdown-menu-end me-lg-3 fs-11px mt-1">
					<button v-on:click="logout" class="dropdown-item d-flex align-items-center">登出 <i class="bi bi-toggle-off ms-auto text-theme fs-16px my-n1"></i></button>
				</div>
			</div>
		</div>
		<!-- END menu -->
		
		
	</div>
</template>

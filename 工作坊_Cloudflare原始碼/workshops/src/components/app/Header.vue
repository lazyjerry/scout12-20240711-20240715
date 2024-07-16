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
				<span class="brand-text">簽到系統</span>
			</RouterLink>
		</div>
		<!-- END brand -->
		
		<!-- BEGIN menu -->
		<div class="menu">
			
			<div class="menu-item dropdown dropdown-mobile-full">
				<a href="#" data-bs-toggle="dropdown" data-bs-display="static" class="menu-link">
					<div class="menu-img online">
						<img src="/assets/img/user/profile.jpg" alt="Profile" height="60">
					</div>
					<div class="menu-text d-sm-block" id="username-title">尚未登入</div>
				</a>
				<div class="dropdown-menu dropdown-menu-end me-lg-3 fs-11px mt-1">
					
					<button v-on:click="logout" class="dropdown-item d-flex align-items-center">登出 <i class="bi bi-toggle-off ms-auto text-theme fs-16px my-n1"></i></button>
				</div>
			</div>
		</div>
		<!-- END menu -->
		
		<!-- BEGIN menu-search -->
		<form class="menu-search" name="header_search_form" v-on:submit="checkForm">
			<div class="menu-search-container">
				<div class="menu-search-icon"><i class="bi bi-search"></i></div>
				<div class="menu-search-input">
					<input type="text" class="form-control form-control-lg" placeholder="Search menu...">
				</div>
				<div class="menu-search-icon">
					<a href="#" v-on:click="toggleAppHeaderSearch"><i class="bi bi-x-lg"></i></a>
				</div>
			</div>
		</form>
		<!-- END menu-search -->
	</div>

	<div class="player">
    <div id="trigger">
      <audio src="/assets/event.mp3" id="audio" ></audio>
    </div>
  </div>
  
</template>

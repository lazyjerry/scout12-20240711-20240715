import { defineStore } from "pinia";

export const useAppOptionStore = defineStore({
  id: "appOption",
  state: () => {
    return {
    	appMode: 'dark',
    	appThemeClass: '',
    	appCoverClass: '',
			appBoxedLayout: false,
			appHeaderHide: false,
			appHeaderSearchToggled: false,
			appSidebarToggled: true,
			appSidebarCollapsed: false,
			appSidebarMobileToggled: false,
			appSidebarMobileClosed: false,
			appSidebarHide: false,
			appContentFullHeight: false,
			appContentClass: '',
			appTopNav: false,
			appFooter: true,
			appFooterFixed: true,
			appThemePanelToggled: false,
			project:{
				isDev:import.meta.env.DEV,
				apiURL: import.meta.env.VITE_API_URL,
				version: "1.1.1",
			},
		}
  }
});

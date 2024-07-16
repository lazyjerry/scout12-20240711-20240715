import { defineStore } from "pinia";

export const useAppSidebarMenuStore = defineStore({
  id: "appSidebarMenu",
  state: () => {
    return [{
      'text': 'Navigation',
      'is_header': true
    },{
      'url': '/',
      'icon': 'bi bi-house-fill',
      'text': '首頁'
    },{
      'url': '/records',
      'icon': 'bi bi-list',
      'text': '簽到簽出清單'
    },{
      'url': '/members',
      'icon': 'bi bi-people-fill',
      'text': '學員清單'
    },{
      'url': '/workshops',
      'icon': 'bi bi-building-fill',
      'text': '工作坊'
    }]
	}
});

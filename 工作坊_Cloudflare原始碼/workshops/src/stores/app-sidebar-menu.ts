import { defineStore } from "pinia";

export const useAppSidebarMenuStore = defineStore({
  id: "appSidebarMenu",
  state: () => {
    return [{
      'text': 'Navigation',
      'is_header': true
    },{
      'url': '/',
      'icon': 'bi bi-cpu-fill',
      'text': '資料清單'
    },{
      'url': '/signin',
      'icon': 'bi bi-sign-intersection-fill',
      'text': '簽到'
    }, {
      'url': '/signout',
      'icon': 'bi bi-sign-railroad-fill',
      'text': '簽退'
    }]
	}
});

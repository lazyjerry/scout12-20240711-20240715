import { defineStore } from "pinia";

export const useAppTopNavMenuStore = defineStore({
  id: "appTopNavMenu",
  state: () => {
    return [{
      'url': '/',
      'icon': 'bi bi-cpu-fill',
      'text': '資料清單'
    },{
      'url': '/signin',
      'icon': 'bi bi-door-closed-fill',
      'text': '簽到'
    }, {
      'url': '/signout',
      'icon': 'bi bi-door-open-fill',
      'text': '簽退'
    }]
	}
});

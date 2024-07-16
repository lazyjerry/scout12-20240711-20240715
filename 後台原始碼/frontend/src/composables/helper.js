
import * as uiHelper from '@/composables/uiHelper.js';

export function downloadSuccessCallback(rowData){
console.log(rowData);
  
  const link = document.createElement('a');
  link.href = rowData.data.url;
  link.download = 'download';
  link.click();
  URL.revokeObjectURL(link.href);
  $('#header-menu-msg').html("<small>如下載未啟動，點擊<a href='"+rowData.data.url+"'>這裡</a></small>");
}

export function test(axios, appOption){
  axios.post(appOption.project.apiURL + '/auth/login', {}).then((response) => {
    uiHelper.loading(true);
    console.log("response",response);
    
    if(response.status < 300 && typeof response.data === 'object' && response.data !== null){
      console.log("response.data",response.data);
      if("" != response.data.error){
        alert('（'+response.data.error+'）'+response.data.message);
      }
    }else{
      alert("網路錯誤，請稍後再試");
    }
    uiHelper.loading(false);
  });
}


export function uploadFile(axios, url, loginToken, data, file, successCallback, failureCallback, errorCallback ) {
  // 使用 FormData 上傳檔案

  const formData = new FormData();
  if(file != null && typeof file !== 'undefined'){
    formData.append('file', file);
  }

  for (const [key,obj] of Object.entries(data)) {
    formData.append(key,obj);
  };

  axios.post(url, formData,{
      headers: { 
        "auth": "Bearer " + loginToken, 
        "Content-Type": 'multipart/form-data' 
      }
    }).then(response => {
      if(response.status < 300 && typeof response.data === 'object' && response.data !== null){
        console.log("response.data", response.data.error);
        if("" != response.data.error){
          console.log("failureCallback.");
          // alert('（'+response.data.error+'）'+response.data.message);
          failureCallback(response.data);
        }else{
          console.log("successCallback.");
          successCallback(response.data);
        }
      }else{
        errorCallback(response);
      }
      uiHelper.loading(false);
    }).catch(error => {
      errorCallback(error);
      uiHelper.loading(false);
    });
}


export function post(axios, url, loginToken, data, successCallback,failureCallback,errorCallback){
  uiHelper.loading(true);
  axios.post(url, data,{
      headers: { "auth": "Bearer " + loginToken }
    }).then(response => {
      if(response.status < 300 && typeof response.data === 'object' && response.data !== null){
        console.log("response.data", response.data.error);
        if("" != response.data.error){
          console.log("failureCallback");
          // alert('（'+response.data.error+'）'+response.data.message);
          failureCallback(response.data);
        }else{
          console.log("successCallback");
          successCallback(response.data);
        }
      }else{
        errorCallback(response);
      }
      uiHelper.loading(false);
    }).catch(error => {
      errorCallback(error);
      uiHelper.loading(false);
    });
}

export function getToken(router, appVariable){
  let loginToken = localStorage.getItem(appVariable.key.accountSession);
  if(null == loginToken){
    alert("尚未登入");
    router.push('/login');
  }
  return loginToken;
}

export function isNullOrUndefined(value) {
  return value === undefined || value === null;
}

export async function checkLogedIn(axios, router, appOption, appVariable){
  let loginToken = localStorage.getItem(appVariable.key.accountSession);
  if(isNullOrUndefined(loginToken)){
    alert("尚未登入");
    router.push('/login');
  }else{
    await axios.post(appOption.project.apiURL + '/admin/getCurrentUser', {
      },{
      headers: { "auth": "Bearer " + loginToken }
    }).then((response) => {
      if(response.status < 300 && typeof response.data === 'object' && response.data !== null){
        // console.log("response.data", response.data);
        if("" != response.data.error){
          localStorage.clear();
          alert('（'+response.data.error+'）'+response.data.message);
          router.push('login/');
        }else{
          document.getElementById("username-title").innerHTML = response.data.data.name;
          localStorage.setItem(appVariable.key.accountInfo, response.data.data);
        }
      }else{
        localStorage.clear();
        alert("網路錯誤，請稍後再試");
        router.push('login/');
      }
    });
  }
}


export function getFullTime(){
  const dt = new Date();
  const year  = dt.getFullYear().toString();
  const month = (dt.getMonth() + 1).toString().padStart(2, "0");
  const day   = dt.getDate().toString().padStart(2, "0");
  const hour  = dt.getHours().toString().padStart(2, "0");
  const minutes  = dt.getMinutes().toString().padStart(2, "0");
  const seconds  = dt.getSeconds().toString().padStart(2, "0");

  return `${year}-${month}-${day}T${hour}:${minutes}:${seconds}`;
}

export function getFullDate(time){
  const dt = new Date();
  const year  = dt.getFullYear().toString();
  const month = (dt.getMonth() + 1).toString().padStart(2, "0");
  const day   = dt.getDate().toString().padStart(2, "0");

  return `${year}-${month}-${day}T${time}`;
}




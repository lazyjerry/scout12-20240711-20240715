
// import trumpetSfx from "/assets/event.mp3"

export function playAudio(){
  const audio = document.createElement("audio");
  audio.src = "/assets/event.mp3";
  audio.play();
   
  
  // const audio = new Audio(trumpetSfx);  
  // audio.play();
}

export function getToken(router, appVariable){
  let loginToken = localStorage.getItem(appVariable.key.accountSession);
  if(null == loginToken){
    alert("尚未登入");
    router.push('/login');
  }
  return loginToken;
}

export function checkLogedIn(axios, router, appOption, appVariable){
  let loginToken = localStorage.getItem(appVariable.key.accountSession);
  if(null == loginToken){
    alert("尚未登入");
    router.push('/login');
  }else{
    axios.post(appOption.project.apiURL + '/', {
      token:loginToken,
      action:"checkLogin"
    }).then((response) => {

      if(response.data.result.checkSuccess){
        document.getElementById("username-title").innerHTML = response.data.result.name;
        localStorage.setItem(appVariable.key.accountInfo, response.data.result);
      }else{
        alert(response.data.result.errorMessage);
        router.push('login/');
      }
    }).catch(function (error) {

      alert("系統錯誤，請聯繫管理員");
      router.push('login/');
      console.log(error.toJSON());
    });
  }
}

export function getShopNo(appVariable){

  const dateObj = new Date();
  const pMonth        = (dateObj.getUTCMonth() + 1).toString().padStart(2,"0");
  const pDay          = dateObj.getUTCDate().toString().padStart(2,"0");
  const newPaddedDate = `${pMonth}${pDay}`;
  console.log(newPaddedDate);

  let shopNo = localStorage.getItem(appVariable.key.shopKey);
  if(null == shopNo){
    localStorage.setItem(appVariable.key.shopKeyExpire, 0);
    localStorage.setItem(appVariable.key.shopKey, "");
  }else{
    console.log(localStorage.getItem(appVariable.key.shopKeyExpire));
    // 21600000 = 3600*12*1000/2 等於 12 個小時
    // 判斷如果超過 n 小時，且日期開頭與今天不符，則重置
    if(Date.now() - localStorage.getItem(appVariable.key.shopKeyExpire) > 21600000 ||
      !shopNo.startsWith(newPaddedDate)){
      
      localStorage.setItem(appVariable.key.shopKey, "");
      localStorage.setItem(appVariable.key.shopKeyExpire, 0);
      return "";
    }
  }
  return localStorage.getItem(appVariable.key.shopKey)
}

export function saveShopNo(appVariable, shopNo){
  if("" == localStorage.getItem(appVariable.key.shopKey)){
    localStorage.setItem(appVariable.key.shopKey, shopNo);
    localStorage.setItem(appVariable.key.shopKeyExpire, Date.now());
  }
}


// 判斷是否要顯示
export function isSessionExpired(session){
  const dateObj = new Date();
  const pMonth        = (dateObj.getMonth() + 1).toString().padStart(2,"0");
  const pDay          = dateObj.getDate().toString().padStart(2,"0");
  const newPaddedDate = pMonth + pDay + "01";
  // const newPaddedDate = '071401';
  console.log(newPaddedDate);
  let isExp = newPaddedDate > session;
  console.log(isExp);

  return isExp;
}

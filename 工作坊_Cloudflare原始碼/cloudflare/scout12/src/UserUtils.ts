import {getTime, getHash, isNullOrUndefined, getDateTime} from './util.ts';

// 檢查用戶登入
export function checkUserLogin(sessionObj, jsonObj){
  let defData = {
    checkSuccess: false,
    username: "",
    name:"",
    errorMessage: "" 
  };


  if(isNullOrUndefined(sessionObj)){
    defData.errorMessage = "尚未登入";
    return defData;
  // }else if(jsonObj.useranget != sessionObj.useranget){
  //   // 檢查 ip
  //   defData.errorMessage = "異地登入，請重新登入";
  //   return defData;
  }else if(isUserExpire(sessionObj.loginTimestamp, sessionObj.remember)){
    defData.errorMessage = "憑證過期，請重新登入";
    return defData;
  }
  defData.checkSuccess = true;
  defData.username = sessionObj.username;
  defData.name = sessionObj.name
  if(isNullOrUndefined(sessionObj.sessions)){
    sessionObj.sessions = "";
  }
  defData.sessions= sessionObj.sessions
  return defData;
}

// 判斷用戶是否過期
export function isUserExpire(loginTimestamp, remember){
  const nowTimestamp = Date.now();
  if(!remember){
    // 如果超過 24 小時則過期
    return nowTimestamp - loginTimestamp > 24 * 86400000;
  }

  return nowTimestamp - loginTimestamp > 7 * 24 * 86400000;
}
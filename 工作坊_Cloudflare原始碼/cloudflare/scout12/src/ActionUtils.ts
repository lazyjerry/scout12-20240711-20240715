import {getTime, getHash, isNullOrUndefined, getDateTime} from './util.ts';

export async function getAccountKey(username){
  return "acc_" + username;
}

export async function getAccountKeyPrefix(){
  return "acc_";
}

export async function getAdminSessionKey(key){
  return "admin_" + key;
}

export async function getSessionKey(token){
  return "sess_" + token;
}


export async function getSessionKeyPrefix(){
  return "sess_";
}

export async function getSignKey(shopName, memberId){
  return "sign_" + shopName + "_" + memberId;
}

export async function getSignKeyPrefix(){
  return "sign_";
}



// export async function getSignMetaKey(shopName){
//   return "signMeta_" + shopName;
// }

export async function getSignMetaKeyWithShopNo(shopName, shopNo){
  return "signMWS_" + shopName + "_" + shopNo;
}

export async function getSignMetaKeyWithShopNoPrefix(){
  return "signMWS_";
}

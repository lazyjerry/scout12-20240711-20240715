import * as util from './util.ts';
import * as userUtils from './UserUtils.ts';
import * as actionUtils from './ActionUtils.ts';

export async function doAction(jsonObj, action, env){
  // KV https://developers.cloudflare.com/kv/get-started/

  if("test" == action){
    
    // let sessObj = JSON.parse(await env.scout12.list());
    // let key = await getSignMetaKey(jsonObj.username);
    // await env.scout12.delete(key);
    // let arr = JSON.parse(await env.scout12.get(key));
    // return {
      
    // };

  }else if("sync-list" == action || "sync-put" == action){
    // 如果是 admin 來的需要驗證
    // 取 kv 裡面設定的金鑰密碼，如果沒有設定，則採用一個預設的金鑰
    // 輸入參數需要一個 key 和一個 token (未來可做權限)
    /*
      token: loginToken,
      key: "admin",
      data: {}
      action:"sync-list"
     */
    
    // 驗證密鑰，密鑰必須要在 kv 上建立一個指定的 key 開頭為 admin_XXX XXX 為 jsonObj.key 
    // 內容輸入指定的 value 必須符合才能放行
    if(!jsonObj.is_dev){
      let sessionKey = await actionUtils.getAdminSessionKey(jsonObj.key);
      let sessionStr = await env.scout12.get(sessionKey);
      if(util.isNullOrUndefined(sessionStr) || jsonObj.token != sessionStr){
        let obj = {
          checkSuccess: false,
          errorMessage: ""
        };
        obj.errorMessage = "尚未登入，請重新登入後再試一次 ";
        return obj;
      }
    }else{
      if(jsonObj.token != "123456"){
        let obj = {
            checkSuccess: false,
            errorMessage: ""
          };
          obj.errorMessage = "測試 token 失敗";
          return obj;
        }
    }
    

    let defData = {
      checkSuccess: false,
      errorMessage: "",
      data:{},
    };

    if("sync-list" == action){
      /*
      取得 kv 上的資料內容
      取資料時以「當天最近的一個 session」 來取
      data:{
        username:"工作坊編號",
        session:"場次編號",
      }
      */
      let signMetaKey = await actionUtils.getSignMetaKeyWithShopNo(jsonObj.data.username, jsonObj.data.session);
      let arr = JSON.parse(await env.scout12.get(signMetaKey));

      if(util.isNullOrUndefined(arr)){
        defData.data = {};
      }else{
        defData.data = arr;
      }
      defData.checkSuccess = true;
    }else if("sync-put" == action){
      /*
      添加 kv 資料
      注意前綴
      data:{
        type:"類型",
        ...
      }
      */
      if("register" == jsonObj.data.type){
        // 註冊工作坊，會覆蓋。密碼使用 md5 編碼，請於後端就先處理好編碼
        let key = await actionUtils.getAccountKey(jsonObj.data.username);
        let value = {
          name:jsonObj.data.name,
          password: jsonObj.data.password,
          username: jsonObj.data.username,
          sessions: jsonObj.data.sessions
        }
        await env.scout12.put(key, JSON.stringify(value));
        defData.checkSuccess = true;
        defData.data = value;
      }else if("delete" == jsonObj.data.type){
        let target = jsonObj.data.target == 'signlog' ? 'signlog':'workshop';
        let prefix = '';
        let prefix2 = '';
        if(target == 'workshop'){
          prefix = await actionUtils.getAccountKeyPrefix();
          prefix2 = await actionUtils.getSessionKeyPrefix();
        }else{
          prefix = await actionUtils.getSignKeyPrefix();
          prefix2 = await actionUtils.getSignMetaKeyWithShopNoPrefix();
        }

        const value = await env.scout12.list({ prefix: prefix });
        
        defData.data = [];
        for (const [index, keyObj] of Object.entries(value.keys)) {
          let key = keyObj.name;
          let result = await env.scout12.delete(key);
          keyObj.result=result;
          defData.data.push(keyObj);
        }
        
        defData.checkSuccess = true;
        
        defData.prefix = prefix;
        defData.prefix2 = prefix2;

        
        const value2 = await env.scout12.list({ prefix: prefix2 });
        defData.data2 = [];
        for (const [index, keyObj] of Object.entries(value2.keys)) {
          let key = keyObj.name;
          env.scout12.delete(key);
          let result = await env.scout12.delete(key);
          keyObj.result = result;
          defData.data2.push(keyObj);
        }  

      }

      // await env.scout12.put(jsonObj.data.key, JSON.stringify(jsonObj.data.value));
    }
    return defData;
  }else if("list" == action){
    /*
    取得列表 
    session 改成必須要輸入
    */
    let sessionKey = await actionUtils.getSessionKey(jsonObj.token);
      let sessionObj = JSON.parse(await env.scout12.get(sessionKey));
      let obj = userUtils.checkUserLogin(sessionObj, jsonObj);
      if(!obj.checkSuccess){
        obj.errorMessage = "尚未登入，請重新登入後再試一次";
        return obj;
      }

    let username = sessionObj.username;
    const search = jsonObj.search;

    // {"241212":{"111111":{"isSignin":true,"isSignOut":false},"123321":{"isSignin":true,"isSignOut":false},"123456":{"isSignin":true,"isSignOut":false}}}

    let defData = {
      checkSuccess: false,
      errorMessage: "",
      data:{},
    };

    let arr = {};
    

    // if(util.isNullOrUndefined(search) || "" == search || 0 == Object.keys(arr).length){
    //   let signMetaKey = await getSignMetaKey(username);
    //   arr = JSON.parse(await env.scout12.get(signMetaKey));

    //   if(util.isNullOrUndefined(arr)){
    //     defData.data = {};
    //   }else{
    //     defData.data = arr;
    //   }
      
    // }else{
      // 查找如果含有該場次則單獨取該場次出來
      let signMetaKey = await actionUtils.getSignMetaKeyWithShopNo(username, search);
      arr = JSON.parse(await env.scout12.get(signMetaKey));

      if(util.isNullOrUndefined(arr)){
        defData.data = {};
      }else{
        defData.data = arr;
      }
    // }
    defData.checkSuccess = true;

    return defData;
  }else if("putSignIn" == action || "putSignOut" == action){
    // 注意結構限制， 每個工作坊學員不宜太多
    /*
      token: loginToken,
      shopNo: this.shopNo,
      memberId: this.scanResult,
      action:"putSignIn"
     */
    
    // let key = "sess_" + jsonObj.token;
    let sessionKey = await actionUtils.getSessionKey(jsonObj.token);
    let sessionObj = JSON.parse(await env.scout12.get(sessionKey));
    let obj = userUtils.checkUserLogin(sessionObj, jsonObj);
    if(!obj.checkSuccess){
      obj.errorMessage = "尚未登入，請重新登入後再試一次";
      return obj;
    }

    let username = sessionObj.username;

    let signMetaKey = "";
    let arr = "";
    let shopNoArr = {};
    
    
    // ---- 添加一個給方便給後台同步使用 START ------
    signMetaKey = await actionUtils.getSignMetaKeyWithShopNo(username, jsonObj.shopNo);
    arr = JSON.parse(await env.scout12.get(signMetaKey));
    
    if(util.isNullOrUndefined(arr)){
      arr = {};
    }

    shopNoArr = {};
    if(util.isNullOrUndefined(arr[jsonObj.shopNo])){
      arr[jsonObj.shopNo] = shopNoArr;
    }
    shopNoArr = arr[jsonObj.shopNo];
    
    obj = {
        // isSignin: false,
        // isSignOut: false,
        inTime:0,
        outTime:0,
    };

    if("" == jsonObj.memberId|| '0' == jsonObj.memberId){
      let obj = {
          checkSuccess: false,
          errorMessage: ""
        };
        obj.errorMessage = "學員資料有錯，請手動輸入編號或重新掃描";
        return obj;
    }


    if(util.isNullOrUndefined(shopNoArr[jsonObj.memberId])){
      shopNoArr[jsonObj.memberId] = obj;
    }else{
      obj = shopNoArr[jsonObj.memberId];
    }

    if("putSignIn" == action){
      // obj.isSignin = true;
      obj.inTime = util.getTimestamp();
    }else{
      // obj.isSignOut = true;
      obj.outTime= util.getTimestamp();
    }

    shopNoArr[jsonObj.memberId] = obj;
    console.log(shopNoArr);
    arr[jsonObj.shopNo] = shopNoArr;
    console.log(arr);
    await env.scout12.put(signMetaKey, JSON.stringify(arr));
    // ---- END ------

    return {checkSuccess:true};

  }else if("checkLogin" == action){
    // 驗證登入
    // UGLY session 在 KV 上多了會髒掉
    let key = await actionUtils.getSessionKey(jsonObj.token);
    let sessionObj = JSON.parse(await env.scout12.get(key));

    return userUtils.checkUserLogin(sessionObj, jsonObj);

  }else if("login" == action){
    // let key = "acc_" + await util.getHash(jsonObj.username);
    let accountKey = await actionUtils.getAccountKey(jsonObj.username);
    let password = await util.getHash(jsonObj.password);
    let accountObj = JSON.parse(await env.scout12.get(accountKey));
    
    let result = {
      loginSuccess: false,
      token:"",
      sessions:""
    };

    if(!util.isNullOrUndefined(accountObj)){
      result.loginSuccess = (password == accountObj.password);
      if(result.loginSuccess){
        // 登入規則，一個帳號一次只能一個裝置登入

        result.sessions = accountObj.sessions;
        // 登入成功的話寫入 sess_ 匯出 token
        const sessValue = {
          ...accountObj,
          "ip": jsonObj.ip,
          "useranget":jsonObj.useranget,
          "createAt": util.getDateTime(),
          "remember": jsonObj.remember,
          "loginTimestamp": util.getTime(),
        };
        // 固定 session token
        result.token = await util.getHash(JSON.stringify(accountObj.username));
        // let loginKey = "sess_" + token;
        let sessionKey = await actionUtils.getSessionKey(result.token);
        await env.scout12.put(sessionKey, JSON.stringify(sessValue));
      }
    }
    return result;

  }else if("register" == action){
    // 註冊
    // 暫停使用

    let obj = {
      checkSuccess: false,
      username: "",
      name:"",
      errorMessage: "" 
    };
    obj.errorMessage = "目前暫停使用，謝謝。";
    return obj;

    // let key = await actionUtils.getAccountKey(jsonObj.username);
    // let value = {
    //   name:jsonObj.name,
    //   password: await util.getHash(jsonObj.password),
    //   username: jsonObj.username,
    //   sessions: ""
    // }
    // await env.scout12.put(key, JSON.stringify(value));
    // let accountObj = JSON.parse(await env.scout12.get(key));
    // return {
    //   result:accountObj,
    //   "registerSuccess":!util.isNullOrUndefined(accountObj)
    // };
  }
  jsonObj.error = "missing action";
  return jsonObj;
}


/*
KV 結構
需要同一個 namespace

key
用途_KEY值

value
JSON String

 */
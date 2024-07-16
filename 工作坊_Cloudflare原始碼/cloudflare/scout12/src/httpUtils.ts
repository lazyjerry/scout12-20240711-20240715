import { getDate, getTime } from "./util.ts"

export async function handleOptions(request) {
  if (
    request.headers.get("Origin") !== null &&
    request.headers.get("Access-Control-Request-Method") !== null &&
    request.headers.get("Access-Control-Request-Headers") !== null
  ) {
    // Handle CORS preflight requests.
    return new Response(null, {
      headers: {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET,HEAD,POST,OPTIONS",
        "Access-Control-Max-Age": "86400",
        "Access-Control-Allow-Headers": request.headers.get(
          "Access-Control-Request-Headers"
        ),
      },
    });
  } else {
    // Handle standard OPTIONS request.
    return new Response(null, {
      headers: {
        Allow: "GET, HEAD, POST, OPTIONS",
      },
    });
  }
}

// 讀取 json 檔案
export async function getJsonObjRequestBody(request) {
  const contentType = request.headers.get("content-type");
  if (contentType.includes("application/json")) {
    return request.json();
  } else {
    return null;
  }
}

// 讀取 body 全部都轉成 string
export async function readRequestBody(request) {
  const contentType = request.headers.get("content-type");
  if (contentType.includes("application/json")) {
    return JSON.stringify(await request.json());
  } else if (contentType.includes("application/text")) {
    return request.text();
  } else if (contentType.includes("text/html")) {
    return request.text();
  } else if (contentType.includes("form")) {
    const formData = await request.formData();
    const body = {};
    for (const entry of formData.entries()) {
      body[entry[0]] = entry[1];
    }
    return JSON.stringify(body);
  } else {
    // Perhaps some other type of data was submitted in the form
    // like an image, or some other binary data.
    return "a file";
  }
}

export function getRespJson(obj, requestBody){

  return {
    "isSuccess": false,
    "result": obj,
    "request": requestBody,
    "date": getDate(),
    "time": getTime()
  };
}

export async function getResponse(jsonObj, requestBody, isSuccess, status) {
  
  const data = getRespJson(jsonObj, requestBody);
  data.isSuccess = isSuccess;

  let response = new Response(JSON.stringify(data),{
    status:status,
    headers: {
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET,HEAD,POST,OPTIONS",
      "Access-Control-Max-Age": "86400",
      "content-type": "application/json;charset=UTF-8"
      }
  });

  // Append to/Add Vary header so browser will cache response correctly
  // response.headers.append("Vary", "Origin");

  return response;
}
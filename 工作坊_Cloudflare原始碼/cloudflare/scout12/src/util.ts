export function getDate() {
  let tzoffset = 0 * 3600 * 1000;
  return (new Date(Date.now() + tzoffset)).toISOString().split("T")[0]; // "YYYY-MM-DD"
}

export function getTime() {
  let tzoffset = 0 * 3600 * 1000;
  return (new Date(Date.now() + tzoffset)).toISOString().slice(0, -1).split("T")[1]; // "06:16:11.146Z
}

export function getDateTime(){
  let tzoffset = 0 * 3600 * 1000;
  return (new Date(Date.now() + tzoffset)).toISOString();
}

export function getTimestamp(){
  let tzoffset = 0 * 3600 * 1000;
  return Math.floor(new Date(Date.now() + tzoffset)/1000);
}

export function isNullOrUndefined(value) {
  return value === undefined || value === null;
}

export async function getHash(jsonString){
  const msgUint8 = new TextEncoder().encode(jsonString) // encode as (utf-8) Uint8Array
  const hashBuffer = await crypto.subtle.digest('MD5', msgUint8) // hash the message
  const hashArray = Array.from(new Uint8Array(hashBuffer)) // convert buffer to byte array
  const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('') // convert bytes to hex string
  return hashHex;
}


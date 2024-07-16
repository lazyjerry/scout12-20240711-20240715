import {getResponse, getJsonObjRequestBody, handleOptions, getRespJson} from "./httpUtils.ts"
import {doAction} from './action.ts'

export interface Env {
  	scout12: KVNamespace;
}

export default {
	
	async fetch(request: Request, env: Env, ctx: ExecutionContext): Promise<Response> {

		if (request.method === "OPTIONS") {
      // Handle CORS preflight requests
      return handleOptions(request);

    } else if (
      request.method === "GET" ||
      request.method === "HEAD" ||
      request.method === "POST"
    ) {
			// Handle requests to the API server
      return action(request);
    } else {
    	let data = await getRespJson("Method Not Allowed",{});
      return new Response(data, {
        status: 405,
        statusText: "Method Not Allowed",
      });
    }
    // ----

    async function action(request){
    	const args = await getJsonObjRequestBody(request);

    	if(null == args){
    		let data = await getRespJson("It's not json format",request);
    		return new Response(data, {
	        status: 400,
	        statusText: "It's not json format",
	      });
    	}

    	let ip = request.headers.get('cf-connecting-ip') ?? "localhost";
    	args.ip = ip;
      let useranget = request.headers.get('user-agent') ?? "server-request";
      args.useranget = useranget;
      args.is_dev = ip.startsWith('localhost')|| ip.startsWith('127.0');

    	let result = await doAction(args, args.action, env);

    	// let headers = {};
			// let keys = new Map(request.headers).keys();
			// let key;
			// while((key=keys.next().value)){
			//     headers[key] = request.headers.get(key); 
			// }
			// result.headers = headers;

    	return getResponse(result, args, true, 200);
    }

	  // -------

	},
};

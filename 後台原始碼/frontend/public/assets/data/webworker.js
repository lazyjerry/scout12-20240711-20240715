var timerStart = true;

function myTimer(now)
{
   // get current time
   var newNow = new Date();
   var newTime=newNow.valueOf();
   var oldTime = now.valueOf();
   // calculate time difference between now and initial time
   var diff = newTime-oldTime;
   // calculate number of minutes
   var minutes = Math.floor(diff/1000/60);
   // calculate number of seconds
   var seconds = Math.floor(diff/1000)-minutes*60;
   var myVar = null;
   // if number of minutes less than 10, add a leading "0"
   minutes = minutes.toString();
   if (minutes.length == 1){
      minutes = "0"+minutes;
   }
   // if number of seconds less than 10, add a leading "0"
   seconds = seconds.toString();
   if (seconds.length == 1){
      seconds = "0"+seconds;
   }

   // return output to Web Worker
   var result = "";
   // result = minutes+":"+seconds;
   // result +="\t";
   result +=newNow.toLocaleDateString()+" "+newNow.toLocaleTimeString();
   result +="\t";
   result +="From "+now.toLocaleDateString()+" "+now.toLocaleTimeString();
   postMessage(result);
}
               
if (timerStart){
   // get current time
   var now = new Date();
   myVar=setInterval(function(){myTimer(now)},100);
   timerStart = false;
}
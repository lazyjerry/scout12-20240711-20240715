export function loading(isDisabled){
  $('input[type="button"]').prop('disabled',isDisabled);
  $('input[type="submit"]').prop('disabled',isDisabled);
  $('button').prop('disabled',isDisabled);
}

"use strict";
// Display Indonesian amounts while submitting ordinary decimal strings to Laravel.
window.RupiahInput = (() => {
 const selector='input[data-rupiah]';
 const canonical=value=>String(value).replace(/^\s*Rp\s*/i,'').replace(/\s/g,'').replace(/\./g,'').replace(',','.');
 const display=value=>{
  if(value==='')return '';
  const [whole,fraction]=String(value).split('.');
  const grouped=whole.replace(/^0+(?=\d)/,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
  return grouped+(fraction===undefined?'':','+fraction);
 };
 function validate(input){
  const raw=canonical(input.value);
  let error='';
  if(raw!==''&&!/^\d+(?:\.\d{0,2})?$/.test(raw))error='Gunakan angka, titik untuk ribuan, dan koma untuk desimal (contoh: 2.000,50).';
  else if(raw!==''&&Number(raw)>Number(input.dataset.rupiahMax))error='Nominal maksimal Rp'+display(input.dataset.rupiahMax)+'.';
  input.setCustomValidity(error);
 }
 function format(input){
  const position=input.selectionStart??input.value.length;
  const count=input.value.slice(0,position).replace(/[^\d,]/g,'').length;
  const raw=canonical(input.value);
  if(/^\d+(?:\.\d{0,2})?$/.test(raw)){
   input.value=display(raw);
   let cursor=0,seen=0;
   while(cursor<input.value.length&&seen<count){if(/[\d,]/.test(input.value[cursor]))seen++;cursor++;}
   input.setSelectionRange(cursor,cursor);
  }
  validate(input);
 }
 function init(root=document){
  root.querySelectorAll(selector).forEach(input=>{
   if(input.type==='number'){
    const value=input.value;
    input.dataset.rupiahMax=input.max;
    input.type='text';input.inputMode='decimal';
    input.removeAttribute('min');input.removeAttribute('max');input.removeAttribute('step');
    input.value=display(value);
    input.title='Format rupiah: 2.000 atau 2.000,50';
   }
   validate(input);
  });
 }
 document.addEventListener('DOMContentLoaded',()=>{
  init();
  document.addEventListener('input',event=>{if(event.target.matches(selector))format(event.target);},true);
  document.addEventListener('beforeinput',event=>{
   const input=event.target;
   if(!input.matches(selector)||input.selectionStart!==input.selectionEnd)return;
   const pos=input.selectionStart;
   // Delete the adjacent digit with its grouping separator, avoiding a stuck cursor.
   if(event.inputType==='deleteContentBackward'&&input.value[pos-1]==='.')input.setSelectionRange(pos-2,pos);
   if(event.inputType==='deleteContentForward'&&input.value[pos]==='.')input.setSelectionRange(pos,pos+2);
  });
  document.addEventListener('focusout',event=>{
   const input=event.target;if(!input.matches(selector))return;
   if(input.value.endsWith(','))input.value=input.value.slice(0,-1);
   validate(input);
  });
  document.querySelectorAll('form').forEach(form=>form.addEventListener('formdata',event=>{
   form.querySelectorAll(selector).forEach(input=>{
    if(input.name&&!input.disabled)event.formData.set(input.name,canonical(input.value).replace(/\.$/,''));
   });
  }));
 });
 return {init,canonical,display,number:input=>Number(canonical(input.value))||0};
})();

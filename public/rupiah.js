"use strict";
// Whole-rupiah inputs: Indonesian grouping on screen, integer strings on submit.
window.RupiahInput = (() => {
 const selector='input[data-rupiah]';
 const canonical=value=>{
  const text=String(value).replace(/^\s*Rp\s*/i,'').replace(/\s/g,'');
  if (/^\d+$/.test(text) || /^\d{1,3}(?:\.\d{3})+$/.test(text)) return text.replace(/\./g,'');
  return text; // Invalid decimals/grouping stay invalid, never turn 100,50 into 10050.
 };
 const display=value=>{
  if(value==='')return '';
  const [whole,fraction]=String(value).split('.');
  const grouped=whole.replace(/^0+(?=\d)/,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
  return grouped+(fraction===undefined||/^0*$/.test(fraction)?'':','+fraction); // Keep legacy fractions visible for explicit correction.
 };
 function validate(input){
  const raw=canonical(input.value);
  let error='';
  if(raw!==''&&!/^\d+$/.test(raw))error='Gunakan rupiah bulat tanpa desimal, contoh: 100000 atau 100.000.';
  else if(raw!==''&&Number(raw)>Number(input.dataset.rupiahMax))error='Nominal maksimal Rp'+display(input.dataset.rupiahMax)+'.';
  input.setCustomValidity(error);
 }
 function format(input,inputType){
  const position=input.selectionStart??input.value.length;
  const count=input.value.slice(0,position).replace(/[^\d,]/g,'').length;
  let raw=canonical(input.value);
  // Ordinary editing can temporarily break thousands groups; pasted malformed values stay invalid.
  if ((inputType==='insertText'||String(inputType).startsWith('delete')) && /^[\d.]+$/.test(input.value)) raw=input.value.replace(/\./g,'');
  if(/^\d+$/.test(raw)){
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
    input.type='text';input.inputMode='numeric';
    input.removeAttribute('min');input.removeAttribute('max');input.removeAttribute('step');
    input.value=display(value);
    input.title='Rupiah bulat tanpa desimal, contoh: 100.000';
   }
   validate(input);
  });
 }
 document.addEventListener('DOMContentLoaded',()=>{
  init();
  document.addEventListener('input',event=>{if(event.target.matches(selector))format(event.target,event.inputType);},true);
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

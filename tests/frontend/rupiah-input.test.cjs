const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
const callbacks={};
const document={addEventListener:(name,fn)=>callbacks[name]=fn,querySelectorAll:()=>[]};
const context={window:{},document};vm.createContext(context);
vm.runInContext(fs.readFileSync(require.resolve('../../public/rupiah.js'),'utf8'),context);
const api=context.window.RupiahInput;
assert.equal(api.display('100000.00'),'100.000');
assert.equal(api.display('0.00'),'0');
assert.equal(api.canonical('Rp 100.000'),'100000');
assert.equal(api.canonical('100,50'),'100,50');
assert.equal(api.canonical('100.50'),'100.50');
assert.equal(api.display('100.50'),'100,50'); // Legacy fractions must not be silently changed.
function input(value,type='text') {return {value,type,max:'99999999999999',dataset:{rupiahMax:'99999999999999'},selectionStart:value.length,selectionEnd:value.length,matches:()=>true,setCustomValidity(v){this.error=v},removeAttribute(){},setSelectionRange(start,end){this.selectionStart=start;this.selectionEnd=end}};}
let el=input('1000.00','number');api.init({querySelectorAll:()=>[el]});assert.equal(el.value,'1.000');assert.equal(el.inputMode,'numeric');assert.equal(el.error,'');
el=input('100,50');api.init({querySelectorAll:()=>[el]});assert.match(el.error,/tanpa desimal/);
el=input('100000000000000');api.init({querySelectorAll:()=>[el]});assert.match(el.error,/maksimal/);
callbacks.DOMContentLoaded();
el=input('1.0000');callbacks.input({target:el,inputType:'insertText'});assert.equal(el.value,'10.000');assert.equal(el.error,'');
el=input('100.00');callbacks.input({target:el,inputType:'deleteContentBackward'});assert.equal(el.value,'10.000');assert.equal(el.error,'');
el=input('100.50');callbacks.input({target:el,inputType:'insertFromPaste'});assert.match(el.error,/tanpa desimal/);
el=input('100,50');callbacks.input({target:el,inputType:'insertFromPaste'});assert.match(el.error,/tanpa desimal/);
console.log('13 rupiah input scenarios passed.');

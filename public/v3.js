"use strict";
document.addEventListener('DOMContentLoaded',()=>{
 const budgetDialog=document.querySelector('#budget-dialog');
 const budgetButton=document.querySelector('#open-budget-dialog');
 if(budgetDialog){
  budgetButton?.addEventListener('click',()=>budgetDialog.showModal());
  budgetDialog.querySelectorAll('[data-close-budget]').forEach(button=>button.addEventListener('click',()=>budgetDialog.close()));
  budgetDialog.addEventListener('close',()=>budgetButton?.focus());
  if(budgetDialog.dataset.reopen==='yes')budgetDialog.showModal();
 }

 const activityDialog=document.querySelector('#activity-dialog');
 const activityButton=document.querySelector('#open-activity-dialog');
 if(activityDialog){
  activityButton?.addEventListener('click',()=>activityDialog.showModal());
  activityDialog.querySelectorAll('[data-close-activity]').forEach(button=>button.addEventListener('click',()=>activityDialog.close()));
  activityDialog.addEventListener('close',()=>activityButton?.focus());
  if(activityDialog.dataset.reopen==='yes')activityDialog.showModal();
 }

 document.querySelectorAll('input[type="file"][name^="logo_"]').forEach(input=>input.addEventListener('change',()=>{const file=input.files[0];if(!file)return;let img=input.parentElement.parentElement.querySelector('.logo-preview');if(!img){img=document.createElement('img');img.className='logo-preview';img.alt='Pratinjau logo';input.parentElement.after(img);}if(img.dataset.previewUrl)URL.revokeObjectURL(img.dataset.previewUrl);img.dataset.previewUrl=URL.createObjectURL(file);img.src=img.dataset.previewUrl;}));
 const dialog=document.querySelector('#confirm-dialog');let pending=null;
 document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(form.dataset.confirmed==='yes')return;event.preventDefault();pending=form;document.querySelector('#confirm-title').textContent=form.dataset.confirm;document.querySelector('#confirm-reason').value='';dialog.showModal();}));
 dialog?.addEventListener('close',()=>{if(dialog.returnValue==='ok'&&pending){let input=pending.querySelector('input[name="reason"]');if(!input){input=document.createElement('input');input.type='hidden';input.name='reason';pending.append(input);}input.value=document.querySelector('#confirm-reason').value;pending.dataset.confirmed='yes';pending.requestSubmit();}pending=null;});
 const form=document.querySelector('#purchase-form');if(!form)return;
 const money=v=>'Rp'+new Intl.NumberFormat('id-ID',{maximumFractionDigits:0}).format(v);
 function reindex(){RupiahInput.init(form);document.querySelectorAll('[data-item]').forEach((row,i)=>row.querySelectorAll('[data-field]').forEach(el=>el.name=`items[${i}][${el.dataset.field}]`));document.querySelectorAll('#components [data-component]').forEach((row,i)=>row.querySelectorAll('[data-field]').forEach(el=>el.name=`components[${i}][${el.dataset.field}]`));}
 function calc(){let subtotal=0,add=0,potong=0;document.querySelectorAll('[data-item]').forEach(row=>{const qty=Number(row.querySelector('[data-field="volume"]').value)||0;const harga=RupiahInput.number(row.querySelector('[data-field="harga"]'))||0;const val=Math.round(qty*harga+1e-7);subtotal+=val;row.querySelector('[data-amount]').textContent=money(val);});document.querySelectorAll('#components [data-component]').forEach(row=>{const mode=row.querySelector('[data-field="metode"]').value;row.querySelectorAll('[data-mode]').forEach(label=>{const active=label.dataset.mode===mode;label.hidden=!active;label.querySelector('input').disabled=!active;label.querySelector('input').required=active;});const val=mode==='nominal'?Math.round(RupiahInput.number(row.querySelector('[data-field="nominal"]'))||0):Math.round((Number(row.querySelector('[data-field="persentase"]').value)||0)*(RupiahInput.number(row.querySelector('[data-field="dasar"]'))||0)/100+1e-7);row.querySelector('[data-component-amount]').textContent=money(val);const treatment=row.querySelector('[data-field="perlakuan"]').value;if(treatment==='tambah')add+=val;if(treatment==='potong')potong+=val;});Object.entries({subtotal,add,bruto:subtotal+add,potong,netto:subtotal+add-potong}).forEach(([key,v])=>document.querySelector('#sum-'+key).textContent=money(v));}
 document.querySelector('#add-item').addEventListener('click',()=>{if(document.querySelectorAll('[data-item]').length>=200)return;const row=document.querySelector('[data-item]').cloneNode(true);row.querySelectorAll('input').forEach(el=>el.value=el.dataset.field==='volume'?'1':el.dataset.field==='harga'?'0':'');document.querySelector('#items').append(row);reindex();calc();row.querySelector('input').focus();});
 document.querySelector('#add-component').addEventListener('click',()=>{if(document.querySelectorAll('#components [data-component]').length>=30)return;document.querySelector('#components').append(document.querySelector('#component-template').content.cloneNode(true));reindex();calc();});
 form.addEventListener('click',e=>{if(e.target.closest('[data-remove-item]')&&document.querySelectorAll('[data-item]').length>1)e.target.closest('[data-item]').remove();if(e.target.closest('[data-remove-component]'))e.target.closest('[data-component]').remove();reindex();calc();});form.addEventListener('input',calc);form.addEventListener('change',calc);reindex();calc();
});

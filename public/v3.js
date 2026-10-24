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

document.getElementById('vendor-picker')?.addEventListener('change',function(){const o=this.selectedOptions[0];if(!o.value)return;for(const [field,key] of Object.entries({vendor_nama:'name',vendor_alamat:'address',vendor_penerima:'person',vendor_npwp:'tax'})){document.querySelector('[name="'+field+'"]').value=o.dataset[key]||'';}});

document.querySelectorAll('.shell aside nav a').forEach(a=>{if(new URL(a.href,location.href).pathname===location.pathname){a.classList.add('active');a.setAttribute('aria-current','page');}});

// Progressive enhancement: all sections remain readable without JavaScript.
document.querySelectorAll('[data-compact-tabs]').forEach(workspace=>{
 const bar=workspace.querySelector('.compact-tabbar');
 const panels=[...workspace.querySelectorAll(':scope > [data-tab-label]')];
 const key='silpj-tab:'+location.pathname;
 bar.hidden=false;bar.setAttribute('role','tablist');
 const buttons=panels.map(panel=>{
  const button=document.createElement('button');button.type='button';
  button.textContent=panel.dataset.tabLabel;button.id='tab-'+panel.id;
  button.setAttribute('role','tab');button.setAttribute('aria-controls',panel.id);
  panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby',button.id);panel.tabIndex=0;
  bar.append(button);return button;
 });
 function select(index,focus=false){
  panels.forEach((panel,i)=>{panel.hidden=i!==index;buttons[i].setAttribute('aria-selected',String(i===index));buttons[i].tabIndex=i===index?0:-1;});
  try{sessionStorage.setItem(key,panels[index].id);}catch{}
  if(focus)buttons[index].focus();
 }
 buttons.forEach((button,index)=>{
  button.addEventListener('click',()=>select(index));
  button.addEventListener('keydown',event=>{
   let next=index;
   if(event.key==='ArrowRight')next=(index+1)%buttons.length;
   else if(event.key==='ArrowLeft')next=(index-1+buttons.length)%buttons.length;
   else if(event.key==='Home')next=0;
   else if(event.key==='End')next=buttons.length-1;
   else return;
   event.preventDefault();select(next,true);
  });
 });
 let saved='';try{saved=sessionStorage.getItem(key)||'';}catch{}
 const reopened=panels.findIndex(panel=>panel.querySelector('[data-document-reopen="true"]'));
 const requested=panels.findIndex(panel=>panel.id===location.hash.slice(1));
 select(reopened>=0?reopened:requested>=0?requested:Math.max(0,panels.findIndex(panel=>panel.id===saved)));
 workspace.addEventListener('invalid',event=>{const index=panels.findIndex(panel=>panel.contains(event.target));if(index>=0)select(index);},true);
});

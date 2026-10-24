'use strict';
document.querySelectorAll('[data-accounts-workspace]').forEach(workspace=>{
 const rows=[...workspace.querySelectorAll('[data-account-row]')];
 const search=workspace.querySelector('[data-account-search]');
 const create=workspace.querySelector('#account-create');
 const open=workspace.querySelector('[data-open-create]');
 workspace.querySelector('.account-search').hidden=false;
 open.hidden=false;
 open.addEventListener('click',()=>{create.open=true;create.querySelector('input[name="name"]').focus();});
 rows.forEach(row=>row.addEventListener('toggle',()=>{
  if(row.open)rows.forEach(other=>{if(other!==row)other.open=false;});
 }));
 const filter=()=>{
  const query=search.value.trim().toLocaleLowerCase('id');let count=0;
  rows.forEach(row=>{row.hidden=!row.querySelector('summary').textContent.toLocaleLowerCase('id').includes(query);if(!row.hidden)count++;});
  workspace.querySelector('[data-account-count]').textContent=`${count} dari ${rows.length} akun`;
  workspace.querySelector('[data-account-empty]').hidden=count>0;
 };
 search.addEventListener('input',filter);filter();
});
document.querySelectorAll('.school-workspace form').forEach(form=>{
 form.addEventListener('invalid',event=>{
  let parent=event.target.parentElement;
  while(parent && parent!==form.parentElement){if(parent.tagName==='DETAILS')parent.open=true;parent=parent.parentElement;}
 },true);
});
const settings=document.querySelector('.settings-tabs');
if(settings){
 const form=settings.closest('form');
 form.addEventListener('input',()=>{const status=form.querySelector('[data-settings-status]');status.textContent='Ada perubahan. Klik Simpan pengaturan untuk menyimpan.';status.setAttribute('role','status');});
 // Server validation may refer to a field on another tab.
 const error=settings.querySelector('[data-field-error]');
 if(error){const panel=error.closest('[data-tab-label]');document.getElementById('tab-'+panel.id)?.click();}
}

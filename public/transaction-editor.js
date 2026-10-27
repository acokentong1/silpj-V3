'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const form=document.querySelector('#purchase-form');if(!form)return;
 const panels=[...form.querySelectorAll(':scope > section.card')];
 const layout=document.createElement('div');layout.className='editor-layout';
 const content=document.createElement('div');content.className='editor-panels';
 const sidebar=document.createElement('div');sidebar.className='editor-sidebar';
 const nav=document.createElement('div');nav.className='editor-tabs';nav.setAttribute('role','tablist');nav.setAttribute('aria-label','Bagian transaksi');
 form.prepend(layout);layout.append(content,sidebar);content.append(nav);
 panels.forEach(p=>content.append(p));
 sidebar.append(document.querySelector('#editor-summary'));sidebar.append(form.querySelector('.editor-save'));
 const labels=['1. Transaksi & rekanan','2. Barang / jasa','3. Pajak & biaya'];
 const tabs=panels.slice(0,3).map((panel,i)=>{const b=document.createElement('button');b.type='button';b.textContent=labels[i];b.id='editor-tab-'+i;b.setAttribute('role','tab');panel.id='editor-panel-'+i;panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby',b.id);b.setAttribute('aria-controls',panel.id);nav.append(b);b.addEventListener('click',()=>show(i));b.addEventListener('keydown',e=>{let next=i;if(e.key==='ArrowRight')next=(i+1)%3;else if(e.key==='ArrowLeft')next=(i+2)%3;else return;e.preventDefault();show(next);tabs[next].focus();});return b;});
 function show(i){panels.slice(0,3).forEach((p,n)=>{p.hidden=n!==i;tabs[n].setAttribute('aria-selected',String(n===i));tabs[n].tabIndex=n===i?0:-1;});}
 let showingInvalid=false;form.addEventListener('invalid',e=>{if(showingInvalid)return;showingInvalid=true;const i=panels.findIndex(p=>p.contains(e.target));if(i>=0&&i<3)show(i);setTimeout(()=>{showingInvalid=false;},0);},true);show(Number(form.dataset.errorTab)||0);
 const items=form.querySelector('#items'),components=form.querySelector('#components');
 const empty=document.createElement('p');empty.className='muted';empty.textContent='Belum ada pajak atau biaya tambahan. Klik Tambah komponen bila diperlukan.';components.before(empty);
 const money=v=>'Rp'+new Intl.NumberFormat('id-ID').format(v);
 const field=(row,k)=>row.querySelector(`[data-field="${k}"]`);
 const raw=el=>el.hasAttribute('data-rupiah')?RupiahInput.canonical(el.value).replace(/\.$/,''):el.value;
 function index(){RupiahInput.init(form);items.querySelectorAll('[data-item]').forEach((r,i)=>r.querySelectorAll('[data-field]').forEach(e=>e.name=`items[${i}][${e.dataset.field}]`));components.querySelectorAll('[data-component]').forEach((r,i)=>r.querySelectorAll('[data-field]').forEach(e=>e.name=`components[${i}][${e.dataset.field}]`));}
 function calc(){
  empty.hidden=components.children.length>0;
  const error=form.querySelector('#calculation-error');error.hidden=true;
  const itemRows=[...items.querySelectorAll('[data-item]')],rows=[...components.querySelectorAll('[data-component]')];
  itemRows.forEach(r=>field(r,'harga').setCustomValidity(''));RupiahInput.init(form);
  rows.forEach(r=>{const mode=field(r,'metode').value;r.querySelectorAll('[data-mode]').forEach(label=>{const active=label.dataset.mode===mode;label.hidden=!active;label.querySelectorAll('input,select').forEach(input=>{input.disabled=!active;input.required=active;});});});
  try {
   const itemData=itemRows.map(r=>({volume:raw(field(r,'volume')),harga:raw(field(r,'harga'))}));
   const subtotal=TransactionMath.calculate(itemData,[]).subtotal;
   const data=rows.map(r=>{const basis=r.querySelector('[data-base-mode]'),base=field(r,'dasar');base.readOnly=basis.value==='auto';if(base.readOnly)base.value=RupiahInput.display(subtotal.toString());const c={};['metode','perlakuan','kategori','nominal','persentase','dasar'].forEach(k=>c[k]=raw(field(r,k)));r.querySelector('[data-component-help]').textContent=c.perlakuan==='termasuk'?'Hanya rincian: tidak mengubah total.':c.perlakuan==='tambah'?'Ditambahkan ke subtotal untuk membentuk nilai bruto.':'Dikurangi dari bruto untuk menentukan nilai diterima rekanan.';return c;});
   const result=TransactionMath.calculate(itemData,data);
   itemRows.forEach((r,i)=>r.querySelector('[data-amount]').textContent=money(result.amounts[i]));rows.forEach((r,i)=>r.querySelector('[data-component-amount]').textContent=money(result.componentAmounts[i]));
   ['subtotal','add','bruto','potong','netto','tax'].forEach(k=>form.querySelector('#sum-'+k).textContent=money(result[k]));
   if(result.netto<0n||result.bruto>99999999999999n){const message=result.netto<0n?'Potongan melebihi nilai bruto. Periksa tab Pajak & biaya.':'Nilai transaksi melebihi batas.';error.textContent=message;error.hidden=false;field(itemRows[0],'harga').setCustomValidity(message);}
  }catch(e){error.textContent='Periksa angka pada rincian dan komponen. Nominal rupiah harus bulat tanpa desimal.';error.hidden=false;field(itemRows[0],'harga').setCustomValidity(error.textContent);}
 }
 form.querySelector('#add-item').addEventListener('click',()=>{if(items.children.length>=200)return;const row=items.querySelector('[data-item]').cloneNode(true);row.querySelectorAll('input').forEach(e=>{e.value=e.dataset.field==='volume'?'1':e.dataset.field==='harga'?'0':'';e.setCustomValidity('');});items.append(row);index();calc();row.querySelector('input').focus();});
 form.querySelector('#add-component').addEventListener('click',()=>{if(components.children.length>=30)return;components.append(form.querySelector('#component-template').content.cloneNode(true));index();calc();components.lastElementChild.querySelector('input').focus();});
 form.addEventListener('click',e=>{if(e.target.closest('[data-remove-item]')&&items.children.length>1)e.target.closest('[data-item]').remove();else if(e.target.closest('[data-remove-component]'))e.target.closest('[data-component]').remove();else return;index();calc();});
 form.addEventListener('input',calc);form.addEventListener('change',calc);index();calc();
});

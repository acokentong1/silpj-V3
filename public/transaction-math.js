/* Exact decimal arithmetic, matching BCMath's whole-rupiah half-up rounding. */
(function(root){
 'use strict';
 const decimal=v=>{const s=String(v||'0');if(!/^\d+(?:\.\d*)?$/.test(s))throw new Error('Isi angka yang valid.');const [a,b='']=s.split('.');return [BigInt(a+b),10n**BigInt(b.length)];};
 const rounded=(n,d)=>(n*2n+d)/(2n*d);
 const product=(a,b,divisor=1n)=>{const [x,dx]=decimal(a),[y,dy]=decimal(b);return rounded(x*y,dx*dy*divisor);};
 const calculate=(items,components)=>{
  const amounts=items.map(i=>product(i.volume,i.harga));const subtotal=amounts.reduce((a,b)=>a+b,0n);
  let add=0n,potong=0n,tax=0n;
  const componentAmounts=components.map(c=>{const v=c.metode==='persentase'?product(c.persentase,c.dasar,100n):product(c.nominal,'1');if(c.perlakuan==='tambah')add+=v;if(c.perlakuan==='potong')potong+=v;if(c.kategori==='pajak')tax+=v;return v;});
  return {amounts,componentAmounts,subtotal,add,bruto:subtotal+add,potong,netto:subtotal+add-potong,tax};
 };
 const api={calculate,product};if(typeof module!=='undefined')module.exports=api;root.TransactionMath=api;
})(typeof window!=='undefined'?window:globalThis);

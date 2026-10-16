"use strict";
document.addEventListener("DOMContentLoaded", () => {
 const openDialog = (dialog, trigger) => {
  if (!(dialog instanceof HTMLDialogElement)) return;
  dialog.returnFocus = trigger || document.activeElement;
  if (!dialog.open) dialog.showModal();
 };
 document.querySelectorAll("[data-document-open]").forEach(button => {
  button.addEventListener("click", () => openDialog(document.getElementById(button.dataset.documentOpen), button));
 });
 document.querySelectorAll("[data-document-close]").forEach(button => {
  button.addEventListener("click", () => button.closest("dialog")?.close());
 });
 document.querySelectorAll(".document-dialog").forEach(dialog => {
  dialog.addEventListener("close", () => {
   if (dialog.returnFocus instanceof HTMLElement) dialog.returnFocus.focus();
  });
 });
 const paidDate=document.getElementById("document-paid-date");
 const receiptDate=document.getElementById("document-date-kuitansi");
 if(paidDate && receiptDate){
  const syncMin=()=>{if(receiptDate.value)paidDate.min=receiptDate.value;};
  receiptDate.addEventListener("change",syncMin);syncMin();
 }
 const reopen=document.querySelector('.document-dialog[data-document-reopen="true"]');
 if(reopen)openDialog(reopen,document.querySelector(`[data-document-open="${reopen.id}"]`));
});

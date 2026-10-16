"use strict";
document.addEventListener("DOMContentLoaded", () => {
 const open = (dialog, trigger) => {
  if (!(dialog instanceof HTMLDialogElement)) return;
  dialog._returnFocus = trigger || document.activeElement;
  if (!dialog.open) dialog.showModal();
 };
 document.querySelectorAll("[data-open-dialog]").forEach(button => {
  button.addEventListener("click", () => open(document.getElementById(button.dataset.openDialog), button));
 });
 document.querySelectorAll("[data-close-dialog]").forEach(button => {
  button.addEventListener("click", () => button.closest("dialog")?.close());
 });
 document.querySelectorAll(".sa-dialog").forEach(dialog => {
  dialog.addEventListener("close", () => {
   if (dialog._returnFocus instanceof HTMLElement) dialog._returnFocus.focus();
  });
 });
 const reopen = document.querySelector('.sa-dialog[data-reopen="true"]');
 if (reopen) open(reopen, document.querySelector(`[data-open-dialog="${reopen.id}"]`));
});

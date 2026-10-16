/* Browser preview only; the PDF uses the original print styles. */
@media screen {
 body { background:#f1f5f4; }
 .preview-toolbar { position:sticky; top:0; z-index:10; background:#fff; border-bottom:1px solid #dce5e1; font:14px/1.4 Arial,sans-serif; }
 .preview-toolbar-inner { max-width:1240px; margin:auto; padding:16px 28px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
 .preview-button { display:inline-flex; align-items:center; justify-content:center; gap:9px; min-height:42px; padding:10px 16px; border:1px solid #d5e0da; border-radius:9px; background:#fff; color:#25483b; font:600 14px/1.4 Arial,sans-serif; text-decoration:none; cursor:pointer; transition:background .15s,border-color .15s; }
 .preview-button:hover { background:#eff6f2; border-color:#8aa99b; }
 .preview-button svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; flex-shrink:0; }
 .preview-primary { background:#155e49; border-color:#155e49; color:#fff; }
 .preview-primary:hover { background:#104b3a; border-color:#104b3a; }
 .preview-back { padding-left:0; border-color:transparent; background:transparent; }
 .preview-back:hover { background:transparent; border-color:transparent; color:#102f23; }
 .preview-actions { display:flex; gap:10px; flex-shrink:0; }
 .preview-brand { display:flex; gap:8px; align-items:center; color:#193e30; font-weight:700; white-space:nowrap; }
 .preview-brand span { font-size:10px; padding:3px 5px; background:#e9f2ed; border-radius:4px; }
 .preview-workspace { max-width:1000px; margin:auto; padding:32px 28px 56px; }
 .preview-heading { display:flex; align-items:center; justify-content:space-between; gap:24px; font-family:Arial,sans-serif; }
 .preview-heading-copy { min-width:0; }
 .preview-eyebrow { margin:0 0 8px; font-size:10px; letter-spacing:1.8px; font-weight:700; color:#648071; }
 .preview-heading h1 { margin:0; color:#1c352b; font-size:26px; line-height:1.25; letter-spacing:-.6px; }
 .preview-number { margin:9px 0 0; font-size:13px; color:#63756c; overflow-wrap:anywhere; }
 .preview-metadata { display:flex; flex-direction:column; align-items:flex-end; gap:9px; flex-shrink:0; font-size:12px; color:#718078; }
 .preview-status { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:20px; font-size:12px; font-weight:600; }
 .preview-status::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
 .is-active { color:#176044; background:#e0efe6; }
 .is-archive { color:#924329; background:#fae9df; }
 .preview-tip { margin:20px 0 22px; color:#718078; font:12px/1.6 Arial,sans-serif; }
 .preview-scroll { overflow-x:auto; padding:2px 8px 22px; }
 .preview-paper { width:210mm; min-height:297mm; padding:17mm 16mm; margin:0 auto; background:#fff; border:1px solid #e1e6e2; box-shadow:0 5px 22px #233b3010; }
 .preview-button:focus-visible,.preview-scroll:focus-visible { outline:3px solid #36a37a; outline-offset:4px; }
 @media (max-width:640px) {
  .preview-toolbar-inner { padding:12px 16px; gap:12px; flex-wrap:wrap; }
  .preview-brand { margin-left:auto; }
  .preview-actions { width:100%; }
  .preview-actions .preview-button { flex:1; }
  .preview-workspace { padding:24px 12px 32px; }
  .preview-heading { padding:0 4px; align-items:flex-start; gap:12px; }
  .preview-heading h1 { font-size:21px; }
  .preview-number { font-size:12px; }
  .preview-metadata { font-size:11px; }
  .preview-tip { padding:0 4px; }
  .preview-paper { margin:0; }
 }
}
@media print {
 .preview-toolbar,.preview-heading,.preview-tip { display:none!important; }
 .preview-workspace,.preview-scroll,.preview-paper { display:block; width:auto; min-height:0; margin:0; padding:0; border:0; box-shadow:none; overflow:visible; }
}

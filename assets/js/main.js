// Admin curtain card: roll down over site, roll back up on Back to site.
function openAdminCurtain(){const c=document.getElementById('adminCurtain');if(!c)return;c.classList.remove('closing');c.classList.add('open');c.setAttribute('aria-hidden','false');document.body.classList.add('curtain-lock');setTimeout(()=>document.querySelector('#adminCurtain input[name=username]')?.focus(),600);}
function closeAdminCurtain(){const c=document.getElementById('adminCurtain');if(!c)return;if(!c.classList.contains('open')&&!c.classList.contains('closing'))return;c.classList.remove('open');c.classList.add('closing');c.setAttribute('aria-hidden','true');setTimeout(()=>{c.classList.remove('closing');document.body.classList.remove('curtain-lock');},580);}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAdminCurtain();});
document.getElementById('adminCurtain')?.addEventListener('click',e=>{if(e.target.classList?.contains('curtain-shade'))closeAdminCurtain();});
// Availability checker + price estimator + proof toggle
async function checkAvail(){
  const ci=document.getElementById('check_in')?.value, co=document.getElementById('check_out')?.value, rm=document.getElementById('room_id')?.value;
  const box=document.getElementById('availMsg'); if(!box) return;
  if(!ci||!co||!rm){box.innerHTML='';return;}
  const r=await fetch(`check-availability.php?room_id=${rm}&check_in=${ci}&check_out=${co}`).then(x=>x.json());
  box.innerHTML=r.available?`<div class="alert alert-success">Available! ${r.nights} night(s) • Total ${r.total_formatted}</div>`:`<div class="alert alert-danger">${r.message}</div>`;
  const t=document.getElementById('totalHint'); if(t&&r.total_formatted) t.textContent='Estimated total: '+r.total_formatted;
}
['check_in','check_out','room_id'].forEach(id=>document.getElementById(id)?.addEventListener('change',checkAvail));
function toggleProof(){
  const m=document.getElementById('payment_method')?.value;
  document.getElementById('proofWrap').style.display=(m==='Cash')?'none':'block';
}
document.getElementById('payment_method')?.addEventListener('change',toggleProof); toggleProof();

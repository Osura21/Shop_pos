const g="modern-alert-styles",x="modern-alert-toast-root";function u(){if(document.getElementById(g))return;const e=document.createElement("style");e.id=g,e.textContent=`
    .modern-alert-root {
      position: fixed;
      top: 18px;
      right: 18px;
      z-index: 9000;
      display: grid;
      gap: 12px;
      width: min(390px, calc(100vw - 32px));
      pointer-events: none;
    }
    .modern-alert-toast {
      pointer-events: auto;
      display: grid;
      grid-template-columns: 42px 1fr 28px;
      gap: 12px;
      align-items: start;
      padding: 14px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
      backdrop-filter: blur(14px);
      color: #111827;
      transform: translateY(-8px);
      opacity: 0;
      animation: modern-alert-in 0.18s ease forwards;
    }
    .modern-alert-toast--leaving {
      animation: modern-alert-out 0.16s ease forwards;
    }
    .modern-alert-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    .modern-alert-toast--success .modern-alert-icon {
      background: #dcfce7;
      color: #16a34a;
    }
    .modern-alert-toast--error .modern-alert-icon {
      background: #fee2e2;
      color: #ef4444;
    }
    .modern-alert-title {
      margin: 1px 0 3px;
      font-size: 14px;
      font-weight: 900;
      letter-spacing: 0;
      color: #0f172a;
    }
    .modern-alert-message {
      font-size: 13px;
      line-height: 1.45;
      color: #64748b;
      overflow-wrap: anywhere;
    }
    .modern-alert-close {
      border: none;
      background: #f1f5f9;
      color: #64748b;
      width: 28px;
      height: 28px;
      border-radius: 9px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    .modern-alert-dialog-backdrop {
      position: fixed;
      inset: 0;
      z-index: 9100;
      display: grid;
      place-items: center;
      padding: 20px;
      background: rgba(15, 23, 42, 0.38);
      backdrop-filter: blur(8px);
    }
    .modern-alert-dialog {
      width: min(440px, 100%);
      border-radius: 18px;
      background: #ffffff;
      box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
      border: 1px solid rgba(15, 23, 42, 0.08);
      padding: 20px;
    }
    .modern-alert-dialog h3 {
      margin: 0 0 6px;
      font-size: 18px;
      font-weight: 900;
      color: #0f172a;
    }
    .modern-alert-dialog p {
      margin: 0 0 16px;
      font-size: 13px;
      line-height: 1.5;
      color: #64748b;
    }
    .modern-alert-select {
      width: 100%;
      height: 44px;
      border: 1px solid #dbe3ee;
      border-radius: 12px;
      padding: 0 12px;
      font-size: 14px;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
    }
    .modern-alert-select:focus {
      border-color: #38bdf8;
      box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.16);
    }
    .modern-alert-error-text {
      min-height: 18px;
      margin-top: 8px;
      font-size: 12px;
      font-weight: 800;
      color: #ef4444;
    }
    .modern-alert-actions {
      margin-top: 18px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .modern-alert-btn {
      min-height: 40px;
      border: none;
      border-radius: 12px;
      padding: 0 15px;
      font-size: 13px;
      font-weight: 900;
      cursor: pointer;
    }
    .modern-alert-btn--ghost {
      background: #f1f5f9;
      color: #475569;
    }
    .modern-alert-btn--primary {
      background: #0f8fcf;
      color: #ffffff;
      box-shadow: 0 12px 22px rgba(15, 143, 207, 0.22);
    }
    @keyframes modern-alert-in {
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes modern-alert-out {
      to { opacity: 0; transform: translateY(-8px); }
    }
  `,document.head.appendChild(e)}function k(){u();let e=document.getElementById(x);return e||(e=document.createElement("div"),e.id=x,e.className="modern-alert-root",document.body.appendChild(e)),e}function w(e){const o=document.createElement("div");return o.innerHTML=String(e),o.textContent||o.innerText||""}function v({type:e="success",title:o=null,message:c="",duration:s=2400}={}){var a;if(typeof document>"u")return;const m=k(),t=document.createElement("div");t.className=`modern-alert-toast modern-alert-toast--${e}`;const d=e==="error"?"bi bi-x-lg":"bi bi-check2",r=o||(e==="error"?"Action needed":"Success"),l=w(c||r);t.innerHTML=`
    <div class="modern-alert-icon"><i class="${d}"></i></div>
    <div>
      <div class="modern-alert-title">${n(r)}</div>
      <div class="modern-alert-message">${n(l)}</div>
    </div>
    <button type="button" class="modern-alert-close" aria-label="Close alert">
      <i class="bi bi-x"></i>
    </button>
  `;const i=()=>{t.classList.add("modern-alert-toast--leaving"),window.setTimeout(()=>t.remove(),180)};(a=t.querySelector(".modern-alert-close"))==null||a.addEventListener("click",i),m.prepend(t),window.setTimeout(i,s)}function C(e,o={}){v({type:"success",title:o.title||"Success",message:e,duration:o.duration||2200})}function S(e,o={}){v({type:"error",title:o.title||"Action needed",message:e,duration:o.duration||3e3})}function E({title:e,text:o,options:c={},placeholder:s="Select option",confirmText:m="Submit",cancelText:t="Cancel"}={}){return u(),new Promise(d=>{var f,b;const r=document.createElement("div");r.className="modern-alert-dialog-backdrop";const l=Object.entries(c).map(([p,y])=>`<option value="${n(p)}">${n(y)}</option>`).join("");r.innerHTML=`
      <div class="modern-alert-dialog" role="dialog" aria-modal="true">
        <h3>${n(e||"Confirm")}</h3>
        <p>${n(o||"")}</p>
        <select class="modern-alert-select">
          <option value="">${n(s)}</option>
          ${l}
        </select>
        <div class="modern-alert-error-text"></div>
        <div class="modern-alert-actions">
          <button type="button" class="modern-alert-btn modern-alert-btn--ghost" data-action="cancel">${n(t)}</button>
          <button type="button" class="modern-alert-btn modern-alert-btn--primary" data-action="confirm">${n(m)}</button>
        </div>
      </div>
    `;const i=p=>{r.remove(),d(p)},a=r.querySelector(".modern-alert-select"),h=r.querySelector(".modern-alert-error-text");r.addEventListener("click",p=>{p.target===r&&i({isConfirmed:!1,value:null})}),(f=r.querySelector('[data-action="cancel"]'))==null||f.addEventListener("click",()=>{i({isConfirmed:!1,value:null})}),(b=r.querySelector('[data-action="confirm"]'))==null||b.addEventListener("click",()=>{if(!a.value){h.textContent="Please select a reason.";return}i({isConfirmed:!0,value:a.value})}),document.body.appendChild(r),a==null||a.focus()})}function $({title:e,text:o,confirmText:c="Confirm",cancelText:s="Cancel"}={}){return u(),new Promise(m=>{var r,l,i;const t=document.createElement("div");t.className="modern-alert-dialog-backdrop",t.innerHTML=`
      <div class="modern-alert-dialog" role="dialog" aria-modal="true">
        <h3>${n(e||"Confirm action")}</h3>
        <p>${n(o||"")}</p>
        <div class="modern-alert-actions">
          <button type="button" class="modern-alert-btn modern-alert-btn--ghost" data-action="cancel">${n(s)}</button>
          <button type="button" class="modern-alert-btn modern-alert-btn--primary" data-action="confirm">${n(c)}</button>
        </div>
      </div>
    `;const d=a=>{t.remove(),m(a)};t.addEventListener("click",a=>{a.target===t&&d({isConfirmed:!1})}),(r=t.querySelector('[data-action="cancel"]'))==null||r.addEventListener("click",()=>{d({isConfirmed:!1})}),(l=t.querySelector('[data-action="confirm"]'))==null||l.addEventListener("click",()=>{d({isConfirmed:!0})}),document.body.appendChild(t),(i=t.querySelector('[data-action="confirm"]'))==null||i.focus()})}function n(e){return String(e??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;")}export{E as a,$ as c,S as e,C as s};

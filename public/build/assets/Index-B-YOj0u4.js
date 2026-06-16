import{r as y,y as d,G as g,S as w,t as x,k as c,l as a,m as k,e as $,J as j,j as S,F as I,N as B,s as P}from"./index-D9TAK_M5.js";import{V as A}from"./VendorAdminLayout-D3R9b49Z.js";import{D as N}from"./Datatable-BnTPbD48.js";import{e as V}from"./modernAlert-DuHrM4a-.js";import{_ as D}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./usePermission-B1yOV269.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const T={class:"page-container"},M={class:"card-modern"},E={class:"table-container-modern"},o="salesInvoicesTable",L=Object.assign({layout:A},{__name:"Index",setup(R){const u=y(null),p=d(()=>route("vendor.sales.invoices.getdata"));function n(e){return String(e??"").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}function m(e){e&&B.visit(route("vendor.sales.invoices.show",e))}function b(e){e&&window.open(route("vendor.sales.invoices.print",e),"_blank")}function v(e){e&&window.open(route("vendor.sales.invoices.download",e),"_blank")}async function h(e){var s,t;if(e)try{await window.axios.post(route("vendor.pms.room-charge.retry",e)),window.location.reload()}catch(i){V(((t=(s=i==null?void 0:i.response)==null?void 0:s.data)==null?void 0:t.message)||"Unable to retry PMS posting.")}}const f=d(()=>[{data:"invoice_no",name:"invoice_no",render:(e,s,t)=>`
      <div>
        <div class="fw-bold text-dark">${n(e||"-")}</div>
        <div class="text-muted x-small">INV #${n(t==null?void 0:t.id)}</div>
      </div>
    `},{data:"branch_name",name:"branch.name",orderable:!1,render:(e,s,t)=>`
      <span class="branch-pill">
        <i class="bi bi-shop"></i>
        ${n(e||"-")}
      </span>
    `},{data:"seller_display",name:"seller_name",render:e=>`
      <span class="fw-semibold text-dark">${n(e||"-")}</span>
    `},{data:"buyer_display",name:"buyer_name",render:e=>`
      <span class="fw-semibold text-dark">${n(e||"-")}</span>
    `},{data:"type_badge",name:"type",render:e=>e||"-"},{data:"status_badge",name:"status",render:e=>e||"-"},{data:"pms_posting_badge",name:"pms_posting_status",render:e=>e||"-"},{data:"purpose_badge",name:"purpose",render:e=>e||"-"},{data:"kind_badge",name:"kind",render:e=>e||"-"},{data:"total_display",name:"total",render:e=>`
      <span class="fw-bold text-dark">${n(e)}</span>
    `},{data:"issued_at",name:"issued_at",render:e=>`<span class="text-secondary small">${n(e)}</span>`},{data:"id",orderable:!1,searchable:!1,render:(e,s,t)=>`
      <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn-circle js-show-invoice" data-id="${n(e)}" title="Show invoice">
          <i class="bi bi-eye-fill"></i>
        </button>

        <button type="button" class="btn-circle js-print-invoice" data-id="${n(e)}" title="Print invoice">
          <i class="bi bi-printer-fill"></i>
        </button>

        <button type="button" class="btn-circle js-download-invoice" data-id="${n(e)}" title="Download invoice">
          <i class="bi bi-download"></i>
        </button>

        ${(t==null?void 0:t.pms_posting_status)==="failed"||(t==null?void 0:t.pms_posting_status)==="pending"?`
          <button type="button" class="btn-circle js-retry-pms" data-id="${n(e)}" title="Retry PMS posting">
            <i class="bi bi-arrow-clockwise"></i>
          </button>
        `:""}
      </div>
    `}]),_=[{targets:-1,width:"140px"},{targets:"_all",className:"align-middle"}];function l(e){const s=e.target.closest(`#${o} .js-show-invoice`);if(s){m(s.dataset.id);return}const t=e.target.closest(`#${o} .js-print-invoice`);if(t){b(t.dataset.id);return}const i=e.target.closest(`#${o} .js-download-invoice`);if(i){v(i.dataset.id);return}const r=e.target.closest(`#${o} .js-retry-pms`);r&&h(r.dataset.id)}return g(()=>{document.addEventListener("click",l)}),w(()=>{document.removeEventListener("click",l)}),(e,s)=>(P(),x(I,null,[c(k($),{title:"Invoices"}),a("div",T,[a("div",M,[s[1]||(s[1]=j('<div class="card-modern-header" data-v-e3cca7c3><div class="header-content" data-v-e3cca7c3><div class="header-title-group" data-v-e3cca7c3><h1 class="header-title" data-v-e3cca7c3>Invoices</h1><p class="header-subtitle" data-v-e3cca7c3> View and manage issued sales invoices with branch, buyer, payment and tax details. </p></div></div></div>',1)),a("div",E,[c(N,{ref_key:"dtRef",ref:u,id:o,url:p.value,columns:f.value,columnDefs:_,order:[[10,"desc"]],searchPlaceholder:"Search by invoice no, seller, buyer, branch or status..."},{header:S(()=>[...s[0]||(s[0]=[a("tr",null,[a("th",null,"Invoice No"),a("th",null,"Branch"),a("th",null,"Seller"),a("th",null,"Buyer"),a("th",null,"Type"),a("th",null,"Status"),a("th",null,"PMS"),a("th",null,"Purpose"),a("th",null,"Kind"),a("th",null,"Total"),a("th",null,"Issued At"),a("th",{class:"text-end"},"Actions")],-1)])]),_:1},8,["url","columns"])])])])],64))}}),Q=D(L,[["__scopeId","data-v-e3cca7c3"]]);export{Q as default};

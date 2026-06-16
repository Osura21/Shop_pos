import{P as m,r as f,y as c,w as b,t as _,k as d,l as t,m as h,e as y,J as v,j as g,F as x,s as k}from"./index-D9TAK_M5.js";import{s as $,e as T}from"./modernAlert-DuHrM4a-.js";import{V as A}from"./VendorAdminLayout-D3R9b49Z.js";import{D as B}from"./Datatable-BnTPbD48.js";import{_ as C}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./usePermission-B1yOV269.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const D={class:"page-container"},I={class:"card-modern"},N={class:"table-container-modern"},V="giftCardTransactionsTable",j=Object.assign({layout:A},{__name:"Index",setup(S){const l=m(),i=f(null),o=c(()=>route("vendor.gift-cards.transactions.getdata"));function s(e=""){return String(e).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}function n(e,r){return r==null||r===""?'<span class="text-muted small">—</span>':`
    <span class="quantity-chip">
      ${s(e||"")} ${s(String(r))}
    </span>
  `}const p=c(()=>[{data:"id",name:"checkbox",orderable:!1,searchable:!1,render:e=>`
      <div class="text-center">
        <input type="checkbox" class="form-check-input js-select-row" value="${e}">
      </div>
    `},{data:"uuid",name:"uuid",render:(e,r,a)=>`
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold  text-nowrap text-dark mb-0">
            ${s(e||"")}
          </div>
          <div class="text-muted x-small">
            TX - ${a.id||"-"}
          </div>
        </div>
      </div>
    `},{data:"gift_card_code",name:"gift_card_id",orderable:!1,searchable:!1,render:e=>`
      <span class="code-chip text-nowrap">${s(e||"-")}</span>
    `},{data:"type_badge",name:"type",render:(e,r,a)=>e||`<span class="status-chip">${s((a==null?void 0:a.type)||"-")}</span>`},{data:"currency_label",name:"currency_code",orderable:!1,searchable:!1,render:e=>`
      <span class="currency-chip">${s(e||"-")}</span>
    `},{data:"amount",name:"amount",render:(e,r,a)=>n(a==null?void 0:a.currency_label,e)},{data:"balance_before",name:"balance_before",render:(e,r,a)=>n(a==null?void 0:a.currency_label,e)},{data:"balance_after",name:"balance_after",render:(e,r,a)=>n(a==null?void 0:a.currency_label,e)},{data:"branch_name",name:"branch_id",orderable:!1,searchable:!1,render:e=>`
      <span class="text-secondary small">${s(e||"-")}</span>
    `},{data:"occurred_at",name:"occurred_at",render:e=>`
      <span class="text-secondary small">${s(e||"-")}</span>
    `}]),u=[{targets:0,className:"text-center align-middle",width:"44px"},{targets:"_all",className:"align-middle"}];return b(()=>l.props.flash,e=>{e!=null&&e.message&&$(e.message),e!=null&&e.error&&T(e.error)},{immediate:!0}),(e,r)=>(k(),_(x,null,[d(h(y),{title:"Gift Card Transactions"}),t("div",D,[t("div",I,[r[1]||(r[1]=v('<div class="card-modern-header" data-v-dfdc0f1b><div class="header-content" data-v-dfdc0f1b><div class="header-title-group" data-v-dfdc0f1b><h1 class="header-title" data-v-dfdc0f1b>Gift Card Transactions</h1><p class="header-subtitle" data-v-dfdc0f1b> Every purchase, redemption, recharge and adjustment is recorded here. </p></div></div></div>',1)),t("div",N,[d(B,{ref_key:"dtRef",ref:i,id:V,url:o.value,columns:p.value,columnDefs:u,order:[[8,"desc"]],searchPlaceholder:"Search here..."},{header:g(()=>[...r[0]||(r[0]=[t("tr",null,[t("th",{class:"text-center",style:{width:"44px"}},[t("input",{type:"checkbox",class:"form-check-input"})]),t("th",null,"Transaction ID"),t("th",null,"Gift Card"),t("th",null,"Type"),t("th",null,"Currency"),t("th",null,"Amount"),t("th",null,"Balance Before"),t("th",null,"Balance After"),t("th",null,"Branch"),t("th",null,"Transaction Date")],-1)])]),_:1},8,["url","columns"])])])])],64))}}),U=C(j,[["__scopeId","data-v-dfdc0f1b"]]);export{U as default};

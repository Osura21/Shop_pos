import{r as i,y as d,t as p,k as n,l as e,m as u,e as h,J as _,j as v,F as b,s as f}from"./index-D9TAK_M5.js";import{V as y}from"./VendorAdminLayout-D3R9b49Z.js";import{D as x}from"./Datatable-BnTPbD48.js";import{_ as g}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./usePermission-B1yOV269.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const A={class:"page-container"},k={class:"card-modern"},P={class:"table-container-modern"},$="salesPaymentsTable",N=Object.assign({layout:y},{__name:"Index",setup(S){const l=i(null),o=d(()=>route("vendor.sales.payments.getdata"));function t(a){return String(a??"").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}const c=d(()=>[{data:"order_no",name:"transaction.order_no",orderable:!1,render:(a,s,r)=>`
      <div>
        <div class="fw-bold text-dark">${t(a||"-")}</div>
        <div class="text-muted x-small">PAY #${t(r==null?void 0:r.id)}</div>
      </div>
    `},{data:"branch_name",name:"transaction.session.register.branch.name",orderable:!1,render:a=>`
      <span class="branch-pill">
        <i class="bi bi-shop"></i>
        ${t(a||"-")}
      </span>
    `},{data:"cashier_name",name:"transaction.cashier_name",orderable:!1,render:a=>`
      <span class="fw-semibold text-dark">${t(a||"System")}</span>
    `},{data:"method_badge",name:"payment_method",render:a=>a||"-"},{data:"amount_display",name:"amount",render:a=>`
      <span class="fw-bold text-dark">${t(a)}</span>
    `},{data:"created_at",name:"created_at",render:a=>`<span class="text-secondary small">${t(a)}</span>`}]),m=[{targets:"_all",className:"align-middle"}];return(a,s)=>(f(),p(b,null,[n(u(h),{title:"Payments"}),e("div",A,[e("div",k,[s[1]||(s[1]=_('<div class="card-modern-header" data-v-ea1c34d3><div class="header-content" data-v-ea1c34d3><div class="header-title-group" data-v-ea1c34d3><h1 class="header-title" data-v-ea1c34d3>Payments</h1><p class="header-subtitle" data-v-ea1c34d3> Track POS payment records by order, branch, cashier, method and amount. </p></div></div></div>',1)),e("div",P,[n(x,{ref_key:"dtRef",ref:l,id:$,url:o.value,columns:c.value,columnDefs:m,order:[[5,"desc"]],searchPlaceholder:"Search by order no, branch, cashier or payment method..."},{header:v(()=>[...s[0]||(s[0]=[e("tr",null,[e("th",null,"Order No"),e("th",null,"Branch"),e("th",null,"Cashier"),e("th",null,"Method"),e("th",null,"Amount"),e("th",null,"Created At")],-1)])]),_:1},8,["url","columns"])])])])],64))}}),R=g(N,[["__scopeId","data-v-ea1c34d3"]]);export{R as default};

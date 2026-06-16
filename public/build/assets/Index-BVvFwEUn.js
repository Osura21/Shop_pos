import{r as o,y as u,G as F,S as z,t as d,k as p,l as s,m as L,e as q,v,j as A,s as c,o as G,x as f,p as S,z as H,K as W,T as Y,F as J,N as R}from"./index-D9TAK_M5.js";import{V as Q}from"./VendorAdminLayout-D3R9b49Z.js";import{D as X}from"./Datatable-BnTPbD48.js";import{S as V}from"./SelectInput-CZz7zUP2.js";import{_ as Z}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./usePermission-B1yOV269.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./createLucideIcon-CZGdKmNy.js";const ee={class:"page-container"},te={class:"card-modern"},ae={class:"card-modern-header"},se={class:"header-content"},le={class:"header-title-group"},ne={key:0,class:"pms-sync-status"},re={class:"table-container-modern"},oe={class:"cancel-modal-card"},ie={key:0,class:"form-error-box"},de={class:"cancel-grid"},ce={class:"field-group"},ue={key:0,class:"error-text"},ve={class:"field-group"},me={key:0,class:"error-text"},pe={class:"field-group mt-3"},fe={key:0,class:"error-text"},be={class:"cancel-modal-footer"},_e=["disabled"],ge=["disabled"],ye="salesOrdersTable",he=Object.assign({layout:Q},{__name:"Index",props:{registers:{type:Array,default:()=>[]},cancelReasons:{type:Array,default:()=>[]}},setup(D){const C=D,g=o(null),y=new Map,b=o(!1),$=o(!1),M=u(()=>route("vendor.sales.orders.getdata")),m=o({visible:!1,x:0,y:0,rowId:null});u(()=>({left:`${m.value.x}px`,top:`${m.value.y}px`})),u(()=>m.value.rowId&&y.get(String(m.value.rowId))||null);const _=o({visible:!1,row:null}),n=o({register_id:"",reason_id:"",note:""}),l=o({}),i=o(!1),I=u(()=>C.registers||[]),N=u(()=>C.cancelReasons||[]);function r(e){return String(e??"").replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}function O(e,t){const a=String((e==null?void 0:e.id)??t);return y.set(a,{...e}),a}function P(e){e&&R.visit(route("vendor.sales.orders.show",e))}function j(e){l.value={},_.value={visible:!0,row:e},n.value={register_id:e.register_id||"",reason_id:"",note:""}}function h(e=!1){i.value&&!e||(_.value={visible:!1,row:null},n.value={register_id:"",reason_id:"",note:""},l.value={})}function T(){const e=_.value.row;e!=null&&e.id&&(i.value=!0,l.value={},R.patch(route("vendor.sales.orders.cancel",e.id),{register_id:n.value.register_id,reason_id:n.value.reason_id,note:n.value.note},{preserveScroll:!0,onSuccess:()=>{var t,a;i.value=!1,h(!0),(a=(t=g.value)==null?void 0:t.reloadDatatable)==null||a.call(t)},onError:t=>{l.value=t||{}},onFinish:()=>{i.value=!1}}))}async function B(){var e,t;if(!(b.value||$.value)){b.value=!0,$.value=!0;try{const{data:a}=await window.axios.post(route("vendor.sales.orders.sync-pms-payment-statuses"),{limit:25});((a==null?void 0:a.checked)||0)>0&&((t=(e=g.value)==null?void 0:e.reloadDatatable)==null||t.call(e))}catch{}finally{b.value=!1}}}function E(){m.value.visible=!1}const K=u(()=>[{data:"customer_display",name:"customer_name",render:(e,t,a)=>`
      <div class="d-flex align-items-center">
        <div class="order-avatar">
          <i class="bi bi-person"></i>
        </div>
        <div class="ms-2">
          <div class="fw-bold text-dark text-nowrap mb-0">${r(e||"Walk-In Customer")}</div>
          <div class="text-muted x-small">Order #${r(a==null?void 0:a.id)}</div>
        </div>
      </div>
    `},{data:"reference_no",name:"uuid",render:e=>`
      <span class="reference-pill">
        ${r(e)}
      </span>
    `},{data:"branch_name",name:"register.branch.name",orderable:!1,render:(e,t,a)=>`
      <div>
        <div class="fw-semibold text-dark">${r(e||"-")}</div>
        <div class="text-muted x-small">${r((a==null?void 0:a.register_name)||"No register")}</div>
      </div>
    `},{data:"type_badge",name:"channel",render:e=>e||"-"},{data:"status_badge",name:"status",render:e=>e||"-"},{data:"payment_badge",name:"payment_status",render:e=>e||"-"},{data:"total_display",name:"grand_total",render:e=>`
      <span class="fw-bold text-dark">${r(e)}</span>
    `},{data:"created_at",name:"created_at",render:e=>`<span class="text-secondary small">${r(e)}</span>`},{data:"id",orderable:!1,searchable:!1,render:(e,t,a)=>(O(a,e),`
        <div class="d-flex gap-2 justify-content-end">

    <button 
      type="button" 
      class="btn-circle js-edit" 
      data-id="${r(e)}" 
      title="Edit"
    >
      <i class="bi bi-eye-fill"></i>
    </button>

    <button 
      type="button" 
      class="btn-circle btn-circle-danger js-delete" 
      data-id="${r(e)}" 
      data-name="${r(name??"")}" 
      title="Delete"
    >
      <i class="bi bi-x-lg"></i>
    </button>

  </div>
      `)}]),U=[{targets:-1,width:"90px"},{targets:"_all",className:"align-middle"}];function w(e){const t=e.target.closest(".js-edit");if(t){const x=t.dataset.id;P(x);return}const a=e.target.closest(".js-delete");if(a){const x=a.dataset.id,k=y.get(String(x));if(!k||!k.can_cancel)return;j(k);return}!e.target.closest(".order-action-menu")&&!e.target.closest(".cancel-modal-card")&&E()}return F(()=>{document.addEventListener("click",w)}),z(()=>{document.removeEventListener("click",w)}),(e,t)=>(c(),d(J,null,[p(L(q),{title:"Orders"}),s("div",ee,[s("div",te,[s("div",ae,[s("div",se,[s("div",le,[t[4]||(t[4]=s("h1",{class:"header-title"},"Orders",-1)),t[5]||(t[5]=s("p",{class:"header-subtitle"}," Centralized sales order history with branch, customer, payment and status details. ",-1)),b.value?(c(),d("div",ne,[...t[3]||(t[3]=[s("i",{class:"bi bi-arrow-repeat"},null,-1),s("span",null,"Syncing PMS payment status...",-1)])])):v("",!0)])])]),s("div",re,[p(X,{ref_key:"dtRef",ref:g,id:ye,url:M.value,columns:K.value,columnDefs:U,order:[[7,"desc"]],searchPlaceholder:"Search by customer, reference, branch or status...",onReady:B},{header:A(()=>[...t[6]||(t[6]=[s("tr",null,[s("th",null,"Customer"),s("th",null,"Reference No"),s("th",null,"Branch"),s("th",null,"Type"),s("th",null,"Status"),s("th",null,"Payment"),s("th",null,"Total"),s("th",null,"Created"),s("th",{class:"text-end"},"Actions")],-1)])]),_:1},8,["url","columns"])])]),p(Y,{name:"modal-fade"},{default:A(()=>[_.value.visible?(c(),d("div",{key:0,class:"modal-backdrop-custom",onClick:G(h,["self"])},[s("div",oe,[t[9]||(t[9]=s("div",{class:"cancel-modal-header"},[s("h3",null,"Cancel Order"),s("p",null," You are about to cancel this order. Please provide a reason for cancellation. No payment will be refunded unless the order was already paid. ")],-1)),l.value.general?(c(),d("div",ie,f(l.value.general),1)):v("",!0),s("div",de,[s("div",ce,[t[7]||(t[7]=s("label",null,"POS Register",-1)),p(V,{class:S({invalid:l.value.register_id}),id:"pos_register_id",modelValue:n.value.register_id,"onUpdate:modelValue":t[0]||(t[0]=a=>n.value.register_id=a),options:I.value.map(a=>({...a,label:`${a.name} (${a.branch_name})`})),valueKey:"id",labelKey:"label",placeholder:"Pos Register"},null,8,["class","modelValue","options"]),l.value.register_id?(c(),d("div",ue,f(l.value.register_id),1)):v("",!0)]),s("div",ve,[t[8]||(t[8]=s("label",null,"Reason",-1)),p(V,{class:S({invalid:l.value.reason_id}),id:"pos_reason_id",modelValue:n.value.reason_id,"onUpdate:modelValue":t[1]||(t[1]=a=>n.value.reason_id=a),options:N.value.map(a=>({...a,label:`${a.name}`})),valueKey:"id",labelKey:"label",placeholder:"Reason"},null,8,["class","modelValue","options"]),l.value.reason_id?(c(),d("div",me,f(l.value.reason_id),1)):v("",!0)])]),s("div",pe,[H(s("textarea",{"onUpdate:modelValue":t[2]||(t[2]=a=>n.value.note=a),class:S(["note-textarea",{invalid:l.value.note}]),rows:"5",placeholder:"Note"},null,2),[[W,n.value.note]]),l.value.note?(c(),d("div",fe,f(l.value.note),1)):v("",!0)]),s("div",be,[s("button",{type:"button",class:"btn-link-soft",disabled:i.value,onClick:h}," Cancel ",8,_e),s("button",{type:"button",class:"btn-submit-soft",disabled:i.value,onClick:T},f(i.value?"Submitting...":"Submit"),9,ge)])])])):v("",!0)]),_:1})])],64))}}),De=Z(he,[["__scopeId","data-v-b0900a46"]]);export{De as default};

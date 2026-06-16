import{P as B,r as u,y as _,G as E,S as O,w as P,t as y,k as p,l as n,m as $,e as F,v as U,q,j as Q,F as R,N as b,s as k}from"./index-D9TAK_M5.js";import{s as G,e as H}from"./modernAlert-DuHrM4a-.js";import{V as K}from"./VendorAdminLayout-D3R9b49Z.js";import{D as L}from"./Datatable-BnTPbD48.js";import{D as Z}from"./DeleteModal-Dx4wNiD6.js";import{u as z}from"./usePermission-B1yOV269.js";import{_ as J}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const W={class:"page-container"},X={class:"card-modern"},Y={class:"card-modern-header"},ee={class:"header-content"},te={class:"table-container-modern"},f="onlineMenuTable",ne=Object.assign({layout:K},{__name:"Index",setup(ae){const{can:m}=z(),w=B(),g=u(null),A=_(()=>route("vendor.online-menus.getdata")),o=u(!1),c=u({id:null,name:""}),d=u(!1);function i(e=""){return String(e).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}const D=_(()=>[{data:"name",name:"name",render:(e,t,a)=>`
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${e}
          </div>
          <div class="text-muted x-small">
            ZN - ${a.id}
          </div>
        </div>
      </div>
    `},{data:"branch_name",name:"branch_id",orderable:!1,searchable:!1,render:e=>e?`<span class="branch-chip">
           <i class="bi bi-building"></i>
           <span class="branch-chip-text">${i(e)}</span>
         </span>`:'<span class="text-muted small">—</span>'},{data:"menu_name",name:"menu_id",orderable:!1,searchable:!1,render:e=>e?`<span class="branch-chip">
           <i class="bi bi-building"></i>
           <span class="branch-chip-text">${i(e)}</span>
         </span>`:'<span class="text-muted small">—</span>'},{data:"slug",name:"slug",render:e=>e?`<span class="slug-chip"><i class="bi bi-link-45deg"></i> ${i(e)}</span>`:'<span class="text-muted small">—</span>'},{data:"status",name:"is_active",orderable:!1,searchable:!1,render:e=>parseInt(e)===1?`
          <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-check-circle-fill"></i>
            Active
          </span>`:`
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
          <i class="bi bi-x-circle-fill text-danger"></i>
          Inactive
        </span>`},{data:"id",name:"actions",orderable:!1,searchable:!1,render:(e,t,a)=>{const s=i((a==null?void 0:a.name)??""),l=i(e);return`
      <div class="d-flex gap-2 justify-content-end">
        ${m("online-menus.edit")?`<button type="button" class="btn-circle js-edit" data-id="${l}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`:""}
        ${m("online-menus.delete")?`<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${l}" data-name="${s}" title="Delete">
               <i class="bi bi-trash3-fill"></i>
             </button>`:""}
      </div>`}}]),M=[{targets:-1,className:"text-end align-middle",width:"110px"},{targets:"_all",className:"align-middle"}];function j(){b.visit(route("vendor.online-menus.create"))}function N(e){b.visit(route("vendor.online-menus.edit",e))}function C(e,t=""){c.value={id:e,name:t},o.value=!0}function T(){}function I(){var t;const e=(t=c.value)==null?void 0:t.id;e&&(d.value=!0,b.delete(route("vendor.online-menus.destroy",e),{preserveScroll:!0,onSuccess:()=>{o.value=!1,setTimeout(()=>{var a,s;(s=(a=g.value)==null?void 0:a.reloadDatatable)==null||s.call(a)},300)},onError:()=>{d.value=!1},onFinish:()=>{d.value=!1}}))}function S(){const e=window.jQuery;if(!e)return;const t=`#${f}`;e(document).on("click",`${t} .js-edit`,a=>{var l,r;const s=(r=(l=a.currentTarget)==null?void 0:l.dataset)==null?void 0:r.id;s&&N(s)}),e(document).on("click",`${t} .js-delete`,a=>{var r,v,h,x;const s=(v=(r=a.currentTarget)==null?void 0:r.dataset)==null?void 0:v.id,l=((x=(h=a.currentTarget)==null?void 0:h.dataset)==null?void 0:x.name)||"";s&&C(s,l)})}function V(){const e=window.jQuery;if(!e)return;const t=`#${f}`;e(document).off("click",`${t} .js-edit`),e(document).off("click",`${t} .js-delete`)}return E(()=>{S()}),O(()=>{V()}),P(()=>w.props.flash,e=>{e!=null&&e.message&&G(e.message),e!=null&&e.error&&H(e.error)},{immediate:!0}),(e,t)=>(k(),y(R,null,[p($(F),{title:"Online Menus"}),n("div",W,[n("div",X,[n("div",Y,[n("div",ee,[t[2]||(t[2]=n("div",{class:"header-title-group"},[n("h1",{class:"header-title"},"Online Menus"),n("p",{class:"header-subtitle"},"Manage all online menus for this tenant.")],-1)),$(m)("online-menus.create")?(k(),y("button",{key:0,type:"button",class:"btn-primary-modern",onClick:j},[...t[1]||(t[1]=[n("i",{class:"bi bi-plus"},null,-1),n("span",{class:"d-inline-flex align-items-center gap-1 text-nowrap"},[q(" Create "),n("span",{class:"create-text"},"Online Menu")],-1)])])):U("",!0)])]),n("div",te,[p(L,{ref_key:"dtRef",ref:g,id:f,url:A.value,columns:D.value,columnDefs:M,order:[[1,"desc"]],searchPlaceholder:"Search online menu name, slug"},{header:Q(()=>[...t[3]||(t[3]=[n("tr",null,[n("th",null,"Name"),n("th",null,"Branch"),n("th",null,"Menu"),n("th",null,"Slug"),n("th",null,"Activation"),n("th",{class:"text-end"},"Actions")],-1)])]),_:1},8,["url","columns"])])])]),p(Z,{show:o.value,"onUpdate:show":t[0]||(t[0]=a=>o.value=a),"target-id":c.value.id,"target-name":c.value.name,loading:d.value,title:"Delete this online menu?","cancel-label":"Keep Menu","confirm-label":"Delete Menu",onConfirm:I,onClosed:T},null,8,["show","target-id","target-name","loading"])],64))}}),fe=J(ne,[["__scopeId","data-v-3db53e0f"]]);export{fe as default};

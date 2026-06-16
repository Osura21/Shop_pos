import{P as B,r as u,y as _,G as E,S as P,w as F,t as y,k as p,l as n,m as $,e as U,v as q,q as Q,j as R,F as G,N as b,s as D}from"./index-D9TAK_M5.js";import{s as H,e as K}from"./modernAlert-DuHrM4a-.js";import{V as L}from"./VendorAdminLayout-D3R9b49Z.js";import{D as O}from"./Datatable-BnTPbD48.js";import{D as z}from"./DeleteModal-Dx4wNiD6.js";import{u as J}from"./usePermission-B1yOV269.js";import{_ as W}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const X={class:"page-container"},Y={class:"card-modern"},Z={class:"card-modern-header"},ee={class:"header-content"},te={class:"table-container-modern"},f="menuTable",ae=Object.assign({layout:L},{__name:"Index",setup(ne){const{can:m}=J(),w=B(),g=u(null),k=_(()=>route("vendor.menus.getdata")),i=u(!1),o=u({id:null,name:""}),d=u(!1);function c(e=""){return String(e).replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#039;")}const A=_(()=>[{data:"name",name:"name",render:(e,t,a)=>`
      <div class="d-flex align-items-center">
        <div>
          <div class="fw-bold text-dark mb-0">
            ${e}
          </div>
          <div class="text-muted x-small">
            MN - ${a.id}
          </div>
        </div>
      </div>
    `},{data:"branch_name",name:"branch_id",orderable:!1,searchable:!1,render:e=>e?`<span class="branch-chip">
           <i class="bi bi-building"></i>
           <span class="branch-chip-text">${c(e)}</span>
         </span>`:'<span class="text-muted small">—</span>'},{data:"description",name:"description",render:e=>e?`<span class="description-text">${c(e)}</span>`:'<span class="text-muted small">—</span>'},{data:"status",name:"is_active",orderable:!1,searchable:!1,render:e=>parseInt(e)===1?`
          <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning d-inline-flex align-items-center gap-1 px-2 py-1">
            <i class="bi bi-check-circle-fill"></i>
            Active
          </span>`:`
        <span class="badge rounded-pill bg-danger-subtle text-secondary border border-danger d-inline-flex align-items-center gap-1 px-2 py-1">
          <i class="bi bi-x-circle-fill text-danger"></i>
          Inactive
        </span>`},{data:"id",name:"actions",orderable:!1,searchable:!1,render:(e,t,a)=>{const s=c((a==null?void 0:a.name)??""),l=c(e);return`
      <div class="d-flex gap-2 justify-content-end">
        ${m("menus.edit")?`<button type="button" class="btn-circle js-edit" data-id="${l}" title="Edit">
               <i class="bi bi-pencil-fill"></i>
             </button>`:""}
        ${m("menus.delete")?`<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${l}" data-name="${s}" title="Delete">
               <i class="bi bi-trash3-fill"></i>
             </button>`:""}
      </div>`}}]),M=[{targets:-1,className:"text-end align-middle",width:"110px"},{targets:"_all",className:"align-middle"}];function j(){b.visit(route("vendor.menus.create"))}function N(e){b.visit(route("vendor.menus.edit",e))}function C(e,t=""){o.value={id:e,name:t},i.value=!0}function T(){}function I(){var t;const e=(t=o.value)==null?void 0:t.id;e&&(d.value=!0,b.delete(route("vendor.menus.destroy",e),{preserveScroll:!0,onSuccess:()=>{i.value=!1,setTimeout(()=>{var a,s;(s=(a=g.value)==null?void 0:a.reloadDatatable)==null||s.call(a)},300)},onError:()=>{d.value=!1},onFinish:()=>{d.value=!1}}))}function S(){const e=window.jQuery;if(!e)return;const t=`#${f}`;e(document).on("click",`${t} .js-edit`,a=>{var l,r;const s=(r=(l=a.currentTarget)==null?void 0:l.dataset)==null?void 0:r.id;s&&N(s)}),e(document).on("click",`${t} .js-delete`,a=>{var r,v,x,h;const s=(v=(r=a.currentTarget)==null?void 0:r.dataset)==null?void 0:v.id,l=((h=(x=a.currentTarget)==null?void 0:x.dataset)==null?void 0:h.name)||"";s&&C(s,l)})}function V(){const e=window.jQuery;if(!e)return;const t=`#${f}`;e(document).off("click",`${t} .js-edit`),e(document).off("click",`${t} .js-delete`)}return E(()=>{S()}),P(()=>{V()}),F(()=>w.props.flash,e=>{e!=null&&e.message&&H(e.message),e!=null&&e.error&&K(e.error)},{immediate:!0}),(e,t)=>(D(),y(G,null,[p($(U),{title:"Menus"}),n("div",X,[n("div",Y,[n("div",Z,[n("div",ee,[t[2]||(t[2]=n("div",{class:"header-title-group"},[n("h1",{class:"header-title"},"Menus"),n("p",{class:"header-subtitle"},"Manage all menus for this tenant.")],-1)),$(m)("menus.create")?(D(),y("button",{key:0,type:"button",class:"btn-primary-modern",onClick:j},[...t[1]||(t[1]=[n("i",{class:"bi bi-plus"},null,-1),n("span",{class:"d-inline-flex align-items-center gap-1 text-nowrap"},[Q(" Create "),n("span",{class:"create-text"},"Menu")],-1)])])):q("",!0)])]),n("div",te,[p(O,{ref_key:"dtRef",ref:g,id:f,url:k.value,columns:A.value,columnDefs:M,order:[[1,"desc"]],searchPlaceholder:"Search menu name"},{header:R(()=>[...t[3]||(t[3]=[n("tr",null,[n("th",null,"Name"),n("th",null,"Branch"),n("th",null,"Description"),n("th",null,"Status"),n("th",{class:"text-end"},"Actions")],-1)])]),_:1},8,["url","columns"])])])]),p(z,{show:i.value,"onUpdate:show":t[0]||(t[0]=a=>i.value=a),"target-id":o.value.id,"target-name":o.value.name,loading:d.value,title:"Delete this menu?","cancel-label":"Keep Menu","confirm-label":"Delete Menu",onConfirm:I,onClosed:T},null,8,["show","target-id","target-name","loading"])],64))}}),fe=W(ae,[["__scopeId","data-v-d618017b"]]);export{fe as default};

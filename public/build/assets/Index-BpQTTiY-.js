import{e as m,D as n,t as u,k as o,l as t,j as p,F as d,N as g,s as h}from"./index-D9TAK_M5.js";import{V as _}from"./VendorAdminLayout-D3R9b49Z.js";import{D as b}from"./Datatable-BnTPbD48.js";import{s as f,e as y}from"./modernAlert-DuHrM4a-.js";import{_ as H}from"./_plugin-vue_export-helper-DlAUqK2U.js";import{H as w}from"./history-B5GLFDal.js";import"./usePermission-B1yOV269.js";import"./country_telephone_data-Ttk2wK2p.js";/* empty css                       */import"./SelectInput-CZz7zUP2.js";import"./createLucideIcon-CZGdKmNy.js";const v={name:"AuthenticationLogsIndex",layout:_,components:{DataTable:b,Head:m,History:w},computed:{columns(){return[{data:"user_display",name:"user_name",render:(e,a,s)=>this.userCell(s)},{data:"user_agent",name:"user_agent",orderable:!1,searchable:!1,render:(e,a,s)=>this.agentCell(s)},{data:"ip_address",name:"ip_address"},{data:"login_at",name:"login_at"},{data:"logout_at",name:"logout_at"},{data:"show_url",name:"show_url",orderable:!1,searchable:!1,render:e=>`
            <button type="button" class="show-action" onclick="window.showAuthenticationLog('${this.escapeHtml(e)}')" title="Show authentication log">
              <i class="bi bi-eye"></i>
            </button>
          `}]},flash(){return this.$page.props.flash},columnDefs(){return[{targets:"_all",className:"align-middle"},{targets:5,className:"align-middle text-end action-column"}]}},mounted(){window.showAuthenticationLog=e=>g.visit(e)},beforeUnmount(){delete window.showAuthenticationLog},watch:{flash:{handler(e){e!=null&&e.message&&f(e.message),e!=null&&e.error&&y(e.error)},immediate:!0,deep:!0}},methods:{userCell(e){return`
        <span class="activity-user">
          <span class="avatar-dot">${this.escapeHtml(e.user_initials||"U")}</span>
          <span>
            <strong>${this.escapeHtml(e.user_name||"System")}</strong>
            ${e.user_role?`<em>${this.escapeHtml(e.user_role)}</em>`:""}
            <small>${this.escapeHtml(e.user_email||"")}</small>
          </span>
        </span>
      `},agentCell(e){return`
        <span class="agent-line">
          <i class="bi bi-display" title="From Desktop"></i><span>${this.escapeHtml(e.is_desktop||"-")}</span>
          <i class="bi bi-windows" title="Platform"></i><span>${this.escapeHtml(e.platform||"-")}</span>
          <i class="bi bi-globe2" title="Browser"></i><span>${this.escapeHtml(e.browser||"-")}</span>
        </span>
      `},escapeHtml(e=""){return String(e).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;")}}},$={class:"activity-page"},A={class:"activity-title"};function D(e,a,s,L,C,r){const i=n("Head"),l=n("History"),c=n("DataTable");return h(),u(d,null,[o(i,{title:"Authentication Logs"}),t("div",$,[t("div",A,[o(l,{size:20}),a[0]||(a[0]=t("h1",null,"Authentication Logs",-1))]),o(c,{id:"authenticationLogsTable",url:e.route("vendor.activities.authentication-logs.getdata"),columns:r.columns,columnDefs:r.columnDefs,order:[[3,"desc"]],searchPlaceholder:"Search here...",wrapperClass:"activity-table"},{header:p(()=>[...a[1]||(a[1]=[t("tr",null,[t("th",null,"User"),t("th",null,"Agent"),t("th",null,"IP Address"),t("th",null,"Login At"),t("th",null,"Logout At"),t("th")],-1)])]),_:1},8,["url","columns","columnDefs"])])],64)}const j=H(v,[["render",D],["__scopeId","data-v-ab05c4ad"]]);export{j as default};

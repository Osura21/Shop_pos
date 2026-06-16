import '../css/app.css'
import '../css/superadmin.css'
import './bootstrap'

import { createInertiaApp, Head } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/src/js'

import $ from 'jquery'
window.$ = window.jQuery = $

/* Admin-only plugins */
import 'datatables.net-bs5'
import 'datatables.net-responsive-bs5'
import 'datatables.net-buttons-bs5'

import JSZip from 'jszip'
window.JSZip = JSZip

import 'datatables.net-buttons/js/buttons.html5'
import 'datatables.net-buttons/js/buttons.print'

import 'datatables.net-bs5/css/dataTables.bootstrap5.css'
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.css'
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.css'

createInertiaApp({
    title: title => `${title}`,
    resolve: name =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Head', Head)
            .mount(el)
    },
    progress: {
        color: '#f28c00',
        delay: 0,
        showSpinner: false,
    },
})

import '../css/app.css';
import './bootstrap';
import '../css/superadmin.css';
import '../css/multivendor.css';

import { createInertiaApp, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import 'flag-icons/css/flag-icons.min.css';

import $ from 'jquery';
window.$ = window.jQuery = $;

import 'datatables.net-bs5';
import 'datatables.net-responsive-bs5';
import 'datatables.net-buttons-bs5';

import JSZip from 'jszip';
window.JSZip = JSZip;

import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';

import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import 'datatables.net-responsive-bs5/css/responsive.bootstrap5.css';
import 'datatables.net-buttons-bs5/css/buttons.bootstrap5.css';

// Maz UI
import { MazUi } from 'maz-ui/plugins/maz-ui';
import { mazUi } from '@maz-ui/themes/presets/mazUi';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(MazUi, {
                theme: {
                    preset: mazUi,
                    strategy: 'hybrid',
                    mode: 'light',
                },
            })
            .component('Head', Head)
            .mount(el);
    },
    progress: {
        color: '#df1f2d',
        delay: 0,
        showSpinner: false,
    },
});

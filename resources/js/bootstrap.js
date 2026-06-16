import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

import { router } from '@inertiajs/vue3';

router.on('navigate', (event) => {
  setTimeout(() => {
    const page = event.detail.page;
    if (page.props && page.props.flash) {
      page.props.flash.message = null;
      page.props.flash.error = null;
    }
    try {
      const state = window.history.state;
      if (state && state.page && state.page.props && state.page.props.flash) {
        state.page.props.flash.message = null;
        state.page.props.flash.error = null;
        window.history.replaceState(state, '');
      }
    } catch (e) {
      console.error('Failed to clear flash from history state:', e);
    }
  }, 0);
});
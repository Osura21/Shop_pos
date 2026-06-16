const STYLE_ID = 'modern-alert-styles'
const TOAST_ROOT_ID = 'modern-alert-toast-root'

function ensureStyles() {
  if (document.getElementById(STYLE_ID)) return

  const style = document.createElement('style')
  style.id = STYLE_ID
  style.textContent = `
    .modern-alert-root {
      position: fixed;
      top: 18px;
      right: 18px;
      z-index: 9000;
      display: grid;
      gap: 12px;
      width: min(390px, calc(100vw - 32px));
      pointer-events: none;
    }
    .modern-alert-toast {
      pointer-events: auto;
      display: grid;
      grid-template-columns: 42px 1fr 28px;
      gap: 12px;
      align-items: start;
      padding: 14px;
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.16);
      backdrop-filter: blur(14px);
      color: #111827;
      transform: translateY(-8px);
      opacity: 0;
      animation: modern-alert-in 0.18s ease forwards;
    }
    .modern-alert-toast--leaving {
      animation: modern-alert-out 0.16s ease forwards;
    }
    .modern-alert-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    .modern-alert-toast--success .modern-alert-icon {
      background: #dcfce7;
      color: #16a34a;
    }
    .modern-alert-toast--error .modern-alert-icon {
      background: #fee2e2;
      color: #ef4444;
    }
    .modern-alert-title {
      margin: 1px 0 3px;
      font-size: 14px;
      font-weight: 900;
      letter-spacing: 0;
      color: #0f172a;
    }
    .modern-alert-message {
      font-size: 13px;
      line-height: 1.45;
      color: #64748b;
      overflow-wrap: anywhere;
    }
    .modern-alert-close {
      border: none;
      background: #f1f5f9;
      color: #64748b;
      width: 28px;
      height: 28px;
      border-radius: 9px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    .modern-alert-dialog-backdrop {
      position: fixed;
      inset: 0;
      z-index: 9100;
      display: grid;
      place-items: center;
      padding: 20px;
      background: rgba(15, 23, 42, 0.38);
      backdrop-filter: blur(8px);
    }
    .modern-alert-dialog {
      width: min(440px, 100%);
      border-radius: 18px;
      background: #ffffff;
      box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
      border: 1px solid rgba(15, 23, 42, 0.08);
      padding: 20px;
    }
    .modern-alert-dialog h3 {
      margin: 0 0 6px;
      font-size: 18px;
      font-weight: 900;
      color: #0f172a;
    }
    .modern-alert-dialog p {
      margin: 0 0 16px;
      font-size: 13px;
      line-height: 1.5;
      color: #64748b;
    }
    .modern-alert-select {
      width: 100%;
      height: 44px;
      border: 1px solid #dbe3ee;
      border-radius: 12px;
      padding: 0 12px;
      font-size: 14px;
      color: #0f172a;
      background: #f8fafc;
      outline: none;
    }
    .modern-alert-select:focus {
      border-color: #38bdf8;
      box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.16);
    }
    .modern-alert-error-text {
      min-height: 18px;
      margin-top: 8px;
      font-size: 12px;
      font-weight: 800;
      color: #ef4444;
    }
    .modern-alert-actions {
      margin-top: 18px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .modern-alert-btn {
      min-height: 40px;
      border: none;
      border-radius: 12px;
      padding: 0 15px;
      font-size: 13px;
      font-weight: 900;
      cursor: pointer;
    }
    .modern-alert-btn--ghost {
      background: #f1f5f9;
      color: #475569;
    }
    .modern-alert-btn--primary {
      background: #0f8fcf;
      color: #ffffff;
      box-shadow: 0 12px 22px rgba(15, 143, 207, 0.22);
    }
    @keyframes modern-alert-in {
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes modern-alert-out {
      to { opacity: 0; transform: translateY(-8px); }
    }
  `
  document.head.appendChild(style)
}

function toastRoot() {
  ensureStyles()
  let root = document.getElementById(TOAST_ROOT_ID)
  if (!root) {
    root = document.createElement('div')
    root.id = TOAST_ROOT_ID
    root.className = 'modern-alert-root'
    document.body.appendChild(root)
  }
  return root
}

function stripHtml(value) {
  const div = document.createElement('div')
  div.innerHTML = String(value || '')
  return div.textContent || div.innerText || ''
}

export function notify({ type = 'success', title = null, message = '', duration = 2400 } = {}) {
  if (typeof document === 'undefined') return

  const root = toastRoot()
  const toast = document.createElement('div')
  toast.className = `modern-alert-toast modern-alert-toast--${type}`

  const iconClass = type === 'error' ? 'bi bi-x-lg' : 'bi bi-check2'
  const heading = title || (type === 'error' ? 'Action needed' : 'Success')
  const body = stripHtml(message || heading)

  toast.innerHTML = `
    <div class="modern-alert-icon"><i class="${iconClass}"></i></div>
    <div>
      <div class="modern-alert-title">${escapeHtml(heading)}</div>
      <div class="modern-alert-message">${escapeHtml(body)}</div>
    </div>
    <button type="button" class="modern-alert-close" aria-label="Close alert">
      <i class="bi bi-x"></i>
    </button>
  `

  const close = () => {
    toast.classList.add('modern-alert-toast--leaving')
    window.setTimeout(() => toast.remove(), 180)
  }

  toast.querySelector('.modern-alert-close')?.addEventListener('click', close)
  root.prepend(toast)
  window.setTimeout(close, duration)
}

export function success(message, options = {}) {
  notify({ type: 'success', title: options.title || 'Success', message, duration: options.duration || 2200 })
}

export function error(message, options = {}) {
  notify({ type: 'error', title: options.title || 'Action needed', message, duration: options.duration || 3000 })
}

export function selectPrompt({ title, text, options = {}, placeholder = 'Select option', confirmText = 'Submit', cancelText = 'Cancel' } = {}) {
  ensureStyles()

  return new Promise((resolve) => {
    const backdrop = document.createElement('div')
    backdrop.className = 'modern-alert-dialog-backdrop'

    const optionHtml = Object.entries(options)
      .map(([value, label]) => `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`)
      .join('')

    backdrop.innerHTML = `
      <div class="modern-alert-dialog" role="dialog" aria-modal="true">
        <h3>${escapeHtml(title || 'Confirm')}</h3>
        <p>${escapeHtml(text || '')}</p>
        <select class="modern-alert-select">
          <option value="">${escapeHtml(placeholder)}</option>
          ${optionHtml}
        </select>
        <div class="modern-alert-error-text"></div>
        <div class="modern-alert-actions">
          <button type="button" class="modern-alert-btn modern-alert-btn--ghost" data-action="cancel">${escapeHtml(cancelText)}</button>
          <button type="button" class="modern-alert-btn modern-alert-btn--primary" data-action="confirm">${escapeHtml(confirmText)}</button>
        </div>
      </div>
    `

    const close = (result) => {
      backdrop.remove()
      resolve(result)
    }

    const select = backdrop.querySelector('.modern-alert-select')
    const errorText = backdrop.querySelector('.modern-alert-error-text')

    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) close({ isConfirmed: false, value: null })
    })

    backdrop.querySelector('[data-action="cancel"]')?.addEventListener('click', () => {
      close({ isConfirmed: false, value: null })
    })

    backdrop.querySelector('[data-action="confirm"]')?.addEventListener('click', () => {
      if (!select.value) {
        errorText.textContent = 'Please select a reason.'
        return
      }
      close({ isConfirmed: true, value: select.value })
    })

    document.body.appendChild(backdrop)
    select?.focus()
  })
}

export function confirmPrompt({ title, text, confirmText = 'Confirm', cancelText = 'Cancel' } = {}) {
  ensureStyles()

  return new Promise((resolve) => {
    const backdrop = document.createElement('div')
    backdrop.className = 'modern-alert-dialog-backdrop'

    backdrop.innerHTML = `
      <div class="modern-alert-dialog" role="dialog" aria-modal="true">
        <h3>${escapeHtml(title || 'Confirm action')}</h3>
        <p>${escapeHtml(text || '')}</p>
        <div class="modern-alert-actions">
          <button type="button" class="modern-alert-btn modern-alert-btn--ghost" data-action="cancel">${escapeHtml(cancelText)}</button>
          <button type="button" class="modern-alert-btn modern-alert-btn--primary" data-action="confirm">${escapeHtml(confirmText)}</button>
        </div>
      </div>
    `

    const close = (result) => {
      backdrop.remove()
      resolve(result)
    }

    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) close({ isConfirmed: false })
    })

    backdrop.querySelector('[data-action="cancel"]')?.addEventListener('click', () => {
      close({ isConfirmed: false })
    })

    backdrop.querySelector('[data-action="confirm"]')?.addEventListener('click', () => {
      close({ isConfirmed: true })
    })

    document.body.appendChild(backdrop)
    backdrop.querySelector('[data-action="confirm"]')?.focus()
  })
}

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

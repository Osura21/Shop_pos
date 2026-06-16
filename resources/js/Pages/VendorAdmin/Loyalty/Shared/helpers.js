export function escapeHtml(value = '') {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

export function checkboxColumn() {
  return {
    data: 'id',
    name: 'checkbox',
    orderable: false,
    searchable: false,
    render: (data) => `<input type="checkbox" class="form-check-input" value="${data}">`,
  }
}
export function initials(value = '', fallback = '?') {
  const text = String(value || '').trim()

  if (!text) return fallback

  return text
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word.charAt(0).toUpperCase())
    .join('') || fallback
}

export function imageNameColumn(nameField = 'name', imageField = 'icon_url', fallback = 'L') {
  return {
    data: nameField,
    name: nameField,
    render: (value, type, row) => {
      const name = escapeHtml(row?.[nameField] || '-')
      const imageUrl = row?.[imageField]

      const avatar = imageUrl
        ? `
          <div class="loyalty-table-avatar">
            <img src="${escapeHtml(imageUrl)}" alt="${name}" />
          </div>
        `
        : `
          <div class="loyalty-table-avatar loyalty-table-avatar--placeholder">
            ${escapeHtml(initials(name, fallback))}
          </div>
        `

      return `
        <div class="loyalty-name-cell">
          ${avatar}
          <div class="loyalty-name-cell__text">
            <div class="fw-bold text-dark mb-0">${name}</div>
          </div>
        </div>
      `
    },
  }
}
export function actionsColumn(can, permission) {
  return {
    data: 'id',
    name: 'actions',
    orderable: false,
    searchable: false,
    render: (data, type, row) => {
      const label = escapeHtml(row?.name || row?.customer_name || 'record')
      return `
  <div class="d-flex gap-2 justify-content-end">
    ${can(`${permission}.edit`)
            ? `<button type="button" class="btn-circle js-edit" data-id="${data}">
            <i class="bi bi-pencil-fill"></i>
          </button>`
            : ''
          }

    ${can(`${permission}.delete`)
            ? `<button type="button" class="btn-circle btn-circle-danger js-delete" data-id="${data}" data-name="${label}">
            <i class="bi bi-trash3-fill"></i>
          </button>`
            : ''
          }
  </div>
`
      }
  }
}

export function textColumn(data, name = data) {
  return {
    data,
    name,
    render: (value) => `<span class="text-secondary small">${escapeHtml(value || '-')}</span>`,
  }
}

export function rawColumn(data, name = data) {
  return { data, name, orderable: false, searchable: false }
}

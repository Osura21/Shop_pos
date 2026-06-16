export function normalizeCurrencyCode(code) {
  const raw = String(code || '').trim()
  const normalized = raw.toUpperCase()
  const symbolCodes = {
    $: 'USD',
    RS: 'LKR',
    '\u20ac': 'EUR',
    '\u00a3': 'GBP',
    '\ufdfc': 'SAR',
    '\u20b9': 'INR',
    '\u00a5': 'JPY',
    'A$': 'AUD',
    'C$': 'CAD',
    JD: 'JOD',
    QR: 'QAR',
    KD: 'KWD',
  }

  return symbolCodes[raw] || symbolCodes[normalized] || normalized
}

export function currencySymbol(code) {
  const normalized = normalizeCurrencyCode(code)
  const symbols = {
    LKR: 'Rs',
    USD: '$',
    EUR: '\u20ac',
    GBP: '\u00a3',
    SAR: '\ufdfc',
    AED: 'AED',
    JOD: 'JD',
    INR: '\u20b9',
    JPY: '\u00a5',
    AUD: 'A$',
    CAD: 'C$',
    CHF: 'CHF',
    CNY: '\u00a5',
    QAR: 'QR',
    KWD: 'KD',
  }

  return symbols[normalized] || normalized
}

export function formatCurrency(value, code, decimals = 3) {
  const amount = Number.parseFloat(value || 0) || 0

  return `${currencySymbol(code)} ${amount.toLocaleString(undefined, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  })}`
}

import { usePage } from '@inertiajs/vue3'

const featurePermissionMap = {
  seating_plan: [
    'seating-plan.',
    'floors.',
    'zones.',
    'tables.',
    'table-merges.',
  ],
  kitchen: [
    'pos-kitchen.',
    'settings-kitchen-alert.',
  ],
  loyalty_points: [
    'loyalty.',
    'loyalty-programs.',
    'loyalty-tiers.',
    'loyalty-rewards.',
    'loyalty-promotions.',
    'loyalty-customers.',
    'loyalty-gifts.',
    'loyalty-transactions.',
  ],
  gift_cards: [
    'gift-cards.',
    'gift-card-analytics.',
    'gift-card-transactions.',
    'gift-card-batches.',
    'reports.gift-card',
  ],
  promotions: [
    'promotions.',
    'promotion-discounts.',
    'promotion-vouchers.',
  ],
  reports: [
    'reports.',
  ],
  activity_log: [
    'activities.',
    'activity-logs.',
    'authentication-logs.',
  ],
}

function featureForPermission(permission) {
  return Object.entries(featurePermissionMap).find(([, prefixes]) => {
    return prefixes.some((prefix) => permission.startsWith(prefix))
  })?.[0]
}

export function usePermission() {
  const page = usePage()

  const can = (permission) => {
    if (!page.props.auth?.permissions?.includes(permission)) {
      return false
    }

    const requiredFeature = featureForPermission(permission)

    if (!requiredFeature || page.props.auth?.guard !== 'vendor') {
      return true
    }

    const subscription = page.props.vendorSubscription

    if (!subscription?.plan_id) {
      return true
    }

    return (subscription.enabled_features || []).includes(requiredFeature)
  }

  return { can }
}

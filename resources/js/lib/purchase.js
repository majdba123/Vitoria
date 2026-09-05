/**
 * Mirrors User::canPurchase() on the backend. Normal customers (TYPE_USER = 0)
 * and vendors (TYPE_VENDOR = 2) may act as a buyer - a vendor may purchase
 * from another vendor's store, never their own (enforced server-side).
 * Admin (1), Syndicate (3) and Employee (4) accounts are privileged roles
 * and must not add to cart or checkout, even though they remain fully
 * authenticated for their own permissions. Guests (no user) keep the
 * existing guest-cart behaviour and are treated as able to purchase here -
 * they are only gated at authenticated checkout.
 */
export function canPurchase(user) {
    return !user || [0, 2].includes(Number(user.type));
}

/**
 * Only the normal customer type (User::TYPE_USER = 0) may act as a buyer.
 * Admin (1), Vendor (2), Syndicate (3) and Employee (4) accounts are
 * privileged roles and must not add to cart or checkout, even though they
 * remain fully authenticated for their own permissions. Guests (no user)
 * keep the existing guest-cart behaviour and are treated as able to
 * purchase here - they are only gated at authenticated checkout.
 */
export function canPurchase(user) {
    return !user || Number(user.type) === 0;
}

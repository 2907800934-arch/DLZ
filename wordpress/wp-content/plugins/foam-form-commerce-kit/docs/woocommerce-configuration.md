# WooCommerce Configuration Notes

## Store defaults

- Currency: USD
- Taxes: disabled by default
- Base country: United States
- Weight unit: lb
- Dimension unit: in
- Reviews: enabled with star ratings
- AJAX add to cart: enabled

## Shipping logic

- Shipping zone: United States
- Free shipping for orders over $50
- Standard shipping flat rate: $9.99

## Payment gateway placeholders

- WooCommerce Stripe Gateway
- WooCommerce PayPal Payments

Install these when ready for live checkout and configure:

- Stripe publishable key
- Stripe secret key
- PayPal client ID
- PayPal secret

## Suggested tax approach

- Keep taxes off while building
- For launch, connect WooCommerce Tax or Avalara depending on nexus needs

## Recommended review setup

- Enable verified owner reviews
- Add photo review extension later if needed

## Abandoned cart and email

- Klaviyo for list growth and flows
- FunnelKit Automations for local WooCommerce event flows

## Launch checklist

- Replace placeholder contact form
- Upload branded lifestyle photography
- Connect Stripe and PayPal
- Configure transactional email sender
- Test mobile checkout
- Add legal pages: Privacy Policy, Terms, Returns Policy

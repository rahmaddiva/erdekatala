# Login Page Overrides

> **PROJECT:** Erdekatala
> **Generated:** 2026-08-24
> **Page Type:** Authentication / Login (standalone, no app chrome)

> ⚠️ **IMPORTANT:** Rules in this file **override** the Master file (`design-system/erdekatala/MASTER.md`).
> Only deviations from the Master are documented here. For all other rules, refer to the Master.

---

## Page-Specific Rules

### Layout Overrides

- **Pattern:** Split-screen — brand panel (left) + form panel (right); collapses to single column below 992px
- **Max Width:** Form content 400px, centered vertically and horizontally in the form panel
- **Sections:** 1. Brand identity + tagline, 2. Max 3 benefit bullets, 3. Welcome heading, 4. Form (username, password, remember, single CTA), 5. Back-to-public-site link + agency footer
- **Density:** Spacious (Master density dial 3/10 applies — NOT the dashboard high-density default)

### Color Overrides

- **Brand source of truth = landing page tokens** (product already has an established brand; overrides Master's generated navy/blue):
  - Ink `#0f1923`, ink-2 `#1a2744` (brand panel gradient)
  - Primary `#dd4814`, primary-dark `#b0380f`, primary-deep `#96300c`
  - Paper `#ffffff`, paper-2 `#f7f5f1`, border `#e5e1d8`
- **CTA button:** `#b0380f` base (6:1 contrast with white text = AA ✓), hover `#c2410f` (5.1:1 ✓), active `#96300c`
- **Error surface:** bg `#fdf0eb`, border `#f2c4b0`, text `#8f2d0a` (≥5:1 ✓)

### Typography Overrides

- **Heading / brand:** Poppins 600–700 (consistent with landing page)
- **Body / form:** Lexend 400–500 (Master recommendation; readability for government audience)
- Labels 13px/500, inputs 15px/400, helper 12–13.5px

### Component Overrides

- Password field MUST have show/hide toggle (`password-toggle`)
- Visible labels with `for` attribute; never placeholder-only (`input-labels`)
- `autocomplete="username"` / `autocomplete="current-password"` (`autofill-support`)
- Submit button: loading spinner + disabled while submitting (`loading-buttons`, `submit-feedback`)
- Server errors render in `role="alert"` region; handle BOTH `error` (string) and `errors` (array) flash keys
- Inputs & button height ≥ 48px (`touch-friendly-input`)
- Focus ring: 3px brand-primary halo, never remove (`focus-states`)

### Motion Overrides

- Subtle tier: single card entrance (≤450ms ease-out) + 150–200ms state transitions
- `prefers-reduced-motion: reduce` disables all animation

---

## Dependencies

- Font Awesome Free 5.15.4 (bundled locally, `assets/plugins/fontawesome-free`) — FA5 icon names only
- Google Fonts: Poppins + Lexend (preconnect + display=swap)
- No jQuery / Bootstrap / AdminLTE required — vanilla JS only (lighter, per performance rules)

# UI / UX

User interface design guidelines for the **Demo** project.

## UI Stack Decision

| Layer | Technology | Role |
|-------|------------|------|
| Backend views | Inertia.js | Bridges Laravel to React |
| Frontend framework | React 18 | Page components |
| UI components | [shadcn/ui](https://github.com/shadcn-ui/ui) | Copy-paste component library |
| Primitives | Base UI (`@base-ui/react`) | Accessible component primitives |
| Styling | Tailwind CSS 4 | Utility-first CSS |
| Build | Vite 8 | Asset bundling & HMR |
| Icons | Lucide React | Icon set |
| Font | Geist Variable | Primary typeface |

**Architecture:** Laravel handles routing and auth → Inertia renders React pages → shadcn/ui provides UI components.

## Directory Structure

```
resources/js/
├── Pages/                  # Inertia pages (route-level)
│   ├── Auth/               # Login, register, etc.
│   ├── Profile/
│   └── Dashboard.jsx
├── Layouts/                # Page layouts
├── Components/             # Breeze legacy components (migrate to shadcn)
├── components/ui/          # shadcn/ui components (do not edit manually)
├── lib/utils.js            # cn() helper for class merging
└── app.jsx                 # Inertia entry point
```

## shadcn/ui Setup

Initialized via `components.json` at the project root.

### Configuration

| Setting | Value |
|---------|-------|
| Style | `base-nova` |
| Base color | `neutral` |
| CSS variables | Enabled |
| Icon library | `lucide` |
| Path alias | `@/` → `resources/js/` |

### Installed Components

| Component | Path | Use |
|-----------|------|-----|
| Button | `components/ui/button.jsx` | Primary actions |
| Input | `components/ui/input.jsx` | Text fields |
| Label | `components/ui/label.jsx` | Form labels |
| Card | `components/ui/card.jsx` | Content containers |
| Checkbox | `components/ui/checkbox.jsx` | Boolean inputs |
| Dialog | `components/ui/dialog.jsx` | Confirmation modals |
| Dropdown Menu | `components/ui/dropdown-menu.jsx` | User menu |
| Sheet | `components/ui/sheet.jsx` | Mobile navigation |
| Separator | `components/ui/separator.jsx` | Visual dividers |

### Add More Components

```powershell
npx shadcn@latest add dropdown-menu dialog table badge
```

Components are copied into `resources/js/components/ui/` and can be customized.

### Usage Example

```jsx
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';

<Card>
    <CardContent>
        <Label htmlFor="email">Email</Label>
        <Input id="email" type="email" />
        <Button type="submit">Save</Button>
    </CardContent>
</Card>
```

## Design Tokens

CSS variables are defined in `resources/css/app.css` and mapped to Tailwind via `@theme inline`.

| Token | Purpose |
|-------|---------|
| `--background` / `--foreground` | Page background & text |
| `--primary` / `--primary-foreground` | Primary actions |
| `--secondary` / `--muted` | Secondary surfaces |
| `--destructive` | Danger actions |
| `--border` / `--input` / `--ring` | Form & focus states |
| `--radius` | Border radius scale |
| `--sidebar-*` | Sidebar theming (future) |

Dark mode variables are defined under `.dark` — apply the class to `<html>` or a wrapper to enable.

## Typography

| Element | Font | Weight |
|---------|------|--------|
| Body | Geist Variable | 400 |
| UI / Label | Geist Variable | 500 |
| Heading | Geist Variable | 600 |

Loaded via `@fontsource-variable/geist` in `app.css`.

## Component Guidelines

### When to Use shadcn

- All **new** UI — forms, buttons, cards, dialogs, tables, navigation.
- Auth pages (Login already migrated as reference).
- Dashboard and admin panels.

### Breeze Legacy Components

All pages use shadcn/ui. Remaining custom files in `resources/js/Components/`:

| File | Status |
|------|--------|
| `ApplicationLogo.jsx` | SVG logo |
| `InputError.jsx` | Form error message (`text-destructive`) |
| `ui/` | shadcn/ui components |

### Layouts

| Layout | File | Status |
|--------|------|--------|
| Guest (auth) | `Layouts/GuestLayout.jsx` | shadcn Card |
| Authenticated | `Layouts/AuthenticatedLayout.jsx` | shadcn Dropdown + Sheet |

## UX Patterns

### Forms

- Use shadcn `Label` + `Input` pairs.
- Set `aria-invalid` on inputs when validation fails.
- Show errors below the field via `InputError` (or shadcn form patterns).
- Submit buttons use `Button` with `disabled={processing}`.

### Navigation

- Authenticated layout: navbar with user dropdown.
- Guest layout: centered card with logo.
- Active route highlighted in nav.

### Feedback

- Inertia flash messages for success/error.
- Form validation errors from Laravel returned via Inertia `errors` prop.

### Responsive

- Mobile-first with Tailwind breakpoints (`sm`, `md`, `lg`).
- Auth cards: full width on mobile, `max-w-md` on desktop.

### Accessibility

- shadcn/Base UI primitives include ARIA support.
- Always pair `Label` with `htmlFor` matching input `id`.
- Keyboard focus via `focus-visible:ring` (built into shadcn components).

## Development Workflow

```powershell
npm run dev          # Vite HMR
npm run build        # Production build
npx shadcn@latest add <component>   # Add new UI component
```

Edit source files in `resources/js/` and `resources/css/app.css` — never edit `public/build/` directly.

## Key Files

| File | Role |
|------|------|
| `components.json` | shadcn CLI configuration |
| `resources/css/app.css` | Tailwind 4 + shadcn theme variables |
| `vite.config.js` | Vite + React + Tailwind + `@` alias |
| `jsconfig.json` | Path aliases for `@/*` |
| `resources/views/app.blade.php` | Inertia root template |

## References

- [shadcn/ui Docs](https://ui.shadcn.com/docs)
- [shadcn/ui GitHub](https://github.com/shadcn-ui/ui)
- [Inertia.js React](https://inertiajs.com/)
- [Tailwind CSS v4](https://tailwindcss.com/docs)
- [Lucide Icons](https://lucide.dev/)
# UI / UX Guidelines

# SportOS

User interface design guidelines for the SportOS platform.

**Rule: shadcn/ui only.** No Bootstrap, Material UI, Ant Design, Chakra UI, or Tailwind UI component kits.

> **Path note:** Files live in `resources/js/Components/ui/`; import via `@/components/ui/`. Normalize casing on Linux during Phase 1 rebrand. See [DOCUMENTATION.md](DOCUMENTATION.md).

---

## 1. UI Stack

| Layer | Technology | Role |
|-------|------------|------|
| Backend views | Inertia.js | Bridges Laravel to React |
| Frontend | React 18 | Page components |
| UI components | [shadcn/ui](https://github.com/shadcn-ui/ui) | **Only** approved component library |
| Primitives | Base UI (`@base-ui/react`) | Accessible primitives |
| Styling | Tailwind CSS 4 | Utility-first CSS |
| Build | Vite 8 | Asset bundling & HMR |
| Icons | Lucide React | Icon set |
| Font | Geist Variable | Primary typeface |

---

## 2. UI Architecture

```
Laravel (routing, auth, policies)
    │
    ▼
Inertia.js (JSON props + page name)
    │
    ▼
React Pages (resources/js/Pages/)
    │
    ├── Layouts (Guest, Authenticated, Admin, Public)
    └── shadcn/ui components (resources/js/Components/ui/)
```

### Directory Structure (Target)

```
resources/js/
├── Pages/
│   ├── Auth/               # Login, register, etc.
│   ├── Admin/              # Organization, events, sports, users
│   │   ├── Organizations/
│   │   ├── Events/
│   │   ├── Sports/
│   │   └── Users/
│   ├── Public/             # Live results, rankings (Phase 5)
│   └── Profile/
├── Layouts/
│   ├── GuestLayout.jsx
│   ├── AuthenticatedLayout.jsx
│   ├── AdminLayout.jsx     # Sidebar shell (planned)
│   └── PublicLayout.jsx    # (planned)
├── Components/ui/          # shadcn/ui only (import: @/components/ui/)
├── lib/utils.js
└── app.jsx
```

---

## 3. shadcn/ui Configuration

| Setting | Value |
|---------|-------|
| Style | `base-nova` |
| Base color | `neutral` |
| CSS variables | Enabled |
| Icon library | `lucide` |
| Path alias | `@/` → `resources/js/` |

### Installed Components

| Component | Use |
|-----------|-----|
| Button | Primary, secondary, destructive actions |
| Input | Text fields |
| Label | Form labels |
| Card | Content containers |
| Checkbox | Boolean inputs |
| Dialog | Confirmation modals |
| Dropdown Menu | User nav menu |
| Sheet | Mobile navigation |
| Sidebar | Admin shell navigation (Phase 1.7) |
| Breadcrumb | Page hierarchy in admin header |
| Tooltip | Sidebar collapsed labels |
| Skeleton | Loading placeholders |
| Separator | Dividers |
| Table | Data listings |
| Badge | Status, role labels |
| Select | Filters, dropdowns |
| Alert Dialog | Destructive confirmations |

### Components to Add (by Phase)

```powershell
# Phase 1
npx shadcn@latest add sidebar breadcrumb tabs

# Phase 2
npx shadcn@latest add calendar popover command

# Phase 3
npx shadcn@latest add tooltip progress

# Phase 4
npx shadcn@latest add sonner avatar

# Phase 5 (public portal)
npx shadcn@latest add skeleton carousel
```

---

## 4. Layout System

### 4.1 Guest Layout (Auth)

- Centered card with SportOS logo
- `max-w-md` on desktop; full width on mobile
- Used for: login, register, password reset

### 4.2 Authenticated Layout

- Thin wrapper around `AdminLayout` (backward compatible import path)
- All authenticated pages use the admin shell

### 4.3 Admin Layout (Active — Phase 1.7)

```
┌──────────┬──────────────────────────────────────┐
│ Sidebar  │  Header (org switcher, breadcrumbs)  │
│          ├──────────────────────────────────────┤
│ Dashboard│                                      │
│ Orgs     │  Page content                        │
│ Events   │                                      │
│ Sports   │                                      │
│ Users    │                                      │
│ Reports  │                                      │
│ Settings │                                      │
└──────────┴──────────────────────────────────────┘
```

- shadcn `Sidebar` + `SidebarProvider` (`Layouts/AdminLayout.jsx`)
- `AppSidebar` — grouped nav: Platform + Administration
- `OrganizationSwitcher` in header (`Components/OrganizationSwitcher.jsx`)
- Breadcrumb navigation (optional `breadcrumbs` prop per page)
- Collapsible sidebar; mobile uses sheet via `SidebarTrigger`
- `POST /admin/organization/switch` persists tenant in session

### 4.4 Public Layout (Planned — Phase 5)

- SportOS branding + event name header
- No auth required
- Optimized for mobile spectators
- Live results ticker

---

## 5. Page Patterns

### 5.1 List Pages (Index)

Used for: users, organizations, events, athletes, teams.

| Element | Component |
|---------|-----------|
| Page title + action button | Header with `Button` |
| Search + filters | `Input` + `Select` |
| Data table | `Table` with sortable columns |
| Status badges | `Badge` |
| Row actions | `Button` variant outline/destructive |
| Pagination | `Button` group |
| Empty state | Centered message in `TableCell` |
| Delete confirm | `AlertDialog` |

### 5.2 Form Pages (Create / Edit)

| Element | Component |
|---------|-----------|
| Form container | `Card` + `CardHeader` + `CardContent` |
| Fields | `Label` + `Input` / `Select` / `Checkbox` |
| Errors | `InputError` below field |
| Submit | `Button` disabled={processing} |
| Cancel | `Button` variant outline → back link |

### 5.3 Dashboard

| Element | Component |
|---------|-----------|
| KPI cards | `Card` grid (4 columns desktop) |
| Charts | Custom with Tailwind (or chart library TBD) |
| Recent activity | `Table` or list |
| Quick actions | `Button` group |

### 5.4 Bracket View (Phase 3)

- Horizontal tree layout for knockout
- `Card` per match node
- Connector lines via CSS/SVG
- Responsive: scroll horizontally on mobile

### 5.5 Event Module Navigation

Event-scoped pages use `EventModuleNav` — tab order follows the **canonical competition lifecycle** ([FUNCTIONAL_SPEC.md §0](FUNCTIONAL_SPEC.md#0-unified-competition-lifecycle-event-first)):

| Order | Tab | Flow step | Status |
|-------|-----|-----------|--------|
| 1 | Overview | Event dashboard + setup checklist | Active |
| 2 | Sports | Define sukan/acara | Active |
| 3 | Participants | Register fakulti/negeri/negara | Planned |
| 4 | Entries | Participant sport selection | Planned |
| 5 | Athletes | Roster individuals | Active |
| 6 | Teams | Roster teams per entry | Active |
| 7 | Officials | Match officials | Active |
| 8 | Venues | Event venues | Active |
| 9 | Competitions | Draws and brackets | Active |
| 10 | Schedule | Fixtures calendar | Active |
| 11 | Rankings | Standings | Active |
| 12 | Medals | Medal tally | Active |
| 13 | Ceremonies | Medal ceremonies | Active |

**Copy rules:**
- `participant_unit_label` on event drives labels: *Faculty* (SAF), *State* (SUKMA), *Country* (SEA Games)
- Event overview should show a **setup checklist** (steps 1–8) with completion status
- Organization switcher shows **tenants only** — never list contingents (negeri/fakulti) as orgs

**Current implementation:** `EventModuleNav.jsx` — Sports before Athletes/Teams; Participants and Entries tabs pending EP-2/EP-3.

---

## 6. Design Tokens

CSS variables in `resources/css/app.css`:

| Token | Purpose |
|-------|---------|
| `--background` / `--foreground` | Page background & text |
| `--primary` / `--primary-foreground` | Primary actions |
| `--secondary` / `--muted` | Secondary surfaces |
| `--destructive` | Danger actions |
| `--border` / `--input` / `--ring` | Form & focus states |
| `--radius` | Border radius scale |
| `--sidebar-*` | Sidebar theming |

Dark mode: `.dark` class on `<html>`.

### SportOS Branding (Planned)

| Token | Value | Use |
|-------|-------|-----|
| Primary | TBD | CTAs, active nav |
| Accent | TBD | Medals, live indicators |
| Success | Green | Confirmed results |
| Warning | Amber | Pending validation |
| Live | Red pulse | Live match indicator |

---

## 7. Typography

| Element | Font | Weight |
|---------|------|--------|
| Body | Geist Variable | 400 |
| UI / Label | Geist Variable | 500 |
| Heading | Geist Variable | 600 |
| Data / Scores | Geist Variable | 700 (tabular nums) |

---

## 8. UX Patterns

### Forms

- `Label` + `Input` pairs with matching `htmlFor` / `id`
- `aria-invalid` on validation failure
- Errors below field via `InputError`
- `disabled={processing}` on submit
- Multi-step forms use shadcn `Tabs` or step indicator

### Navigation

- Active route highlighted (background `bg-secondary`)
- Admin sidebar: icon + label per module
- Organization switcher: `Select` or `Command` palette
- Breadcrumbs on all admin sub-pages

### Feedback

- Success: green flash banner (current) → shadcn `Sonner` toast (planned)
- Errors: Inertia `errors` prop + field-level messages
- Loading: `Skeleton` placeholders (planned)
- Live updates: optimistic UI + WebSocket refresh (Phase 3)

### Data Tables

- Server-side pagination (Laravel paginator)
- Debounced search (300ms)
- Filter state preserved in URL query string
- Bulk actions: checkbox column + action bar (planned)

### Responsive

- Mobile-first: `sm`, `md`, `lg` breakpoints
- Tables: horizontal scroll on mobile
- Sidebar → Sheet on mobile
- Public portal: score cards stack vertically

---

## 9. Accessibility (WCAG 2.1 AA)

| Requirement | Implementation |
|-------------|----------------|
| Keyboard navigation | shadcn/Base UI focus management |
| Screen readers | ARIA labels on icons, tables, forms |
| Color contrast | 4.5:1 minimum on text |
| Focus visible | `focus-visible:ring` (built into shadcn) |
| Live regions | `aria-live="polite"` for live results (Phase 5) |
| Form labels | Always pair Label + Input |

---

## 10. Current Pages

| Page | Path | Status |
|------|------|--------|
| Welcome | `/` | Active |
| Login / Register | `/login`, `/register` | Active |
| Dashboard | `/dashboard` | Active |
| Profile | `/profile` | Active |
| Admin Users | `/admin/users` | Active |

## Planned Pages (by Phase)

| Phase | Pages |
|-------|-------|
| 1 | Organizations, Events, Roles, Audit Logs, Admin Dashboard |
| 2 | Sports, Athletes, Teams, Officials, Venues, Schedule |
| 3 | Competitions, Brackets, Results Entry, Rankings, Medals |
| 4 | Accreditations, Certificates, Reports, Announcements |
| 5 | Public Event, Live Results, Medal Table, Schedule |
| 6 | AI Insights Dashboard, AI Chat Panel |

---

## 11. Development Workflow

```powershell
npm run dev
npm run build
npx shadcn@latest add <component>
```

Never edit `public/build/` directly.

---

## 12. Related Documents

| Document | Link |
|----------|------|
| Architecture | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Functional spec | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
| PRD | [PRD.md](PRD.md) |
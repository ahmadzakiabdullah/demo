# Functional Specification

# SportOS

Detailed functional requirements for all SportOS modules.

### Status Legend

| Status | Meaning |
|--------|---------|
| **Implemented** | Code exists; see [MODULES.md](MODULES.md) |
| **Partial** | Started; does not meet full spec |
| **Planned** | Designed only — no code yet |

Most requirements below are **Planned**. Only auth and admin user items are **Implemented** or **Partial**.

Index: [DOCUMENTATION.md](DOCUMENTATION.md)

---

## 0. Unified Competition Lifecycle (Event-First)

SportOS uses **one operational workflow** for every multi-unit games scenario — university carnival (SAF), state games (SUKMA / MSSM), and international multi-sport games (SEA Games). Only labels and participant types change; the steps and data model stay the same.

### 0.1 Canonical Flow

```mermaid
flowchart LR
    A[1. Event] --> B[2. Sports & Categories]
    B --> C[3. Register Participants]
    C --> D[4. Sport Entries]
    D --> E[5. Athletes & Teams]
    E --> F[6. Schedule]
    F --> G[7. Competitions & Results]
    G --> H[8. Rankings & Medals]
```

| Step | Operator action | Output |
|------|-----------------|--------|
| **1** | Select or create **Event** | Event record (`draft` → `published` → `active`) |
| **2** | Select or create **Sports / Acara** contested at this event | Sports, disciplines, categories, divisions |
| **3** | Register **Participants** (competing units) | `event_participants` roster |
| **4** | Each participant **chooses sports/categories** to enter | `participant_sport_entries` (approval workflow) |
| **5** | Register **athletes** and form **teams** per entry | Athletes, teams, rosters, `registrations` |
| **6** | Build **schedule** — fixtures, venues, officials | Fixtures, matches |
| **7** | Run **competitions** and record **results** | Results, appeals, live scores |
| **8** | Publish **rankings** and **medals** | Standings, medal tally, ceremonies |

Steps 6–8 are **implemented** (Phase 2–3). Steps 3–4 require the **Event Participants** module (planned refactor). Step 5 is **partial** — athletes/teams exist but are not yet driven by participant sport entries.

### 0.2 Terminology

| Concept | Meaning | UI visibility |
|---------|---------|---------------|
| **Organization** | SaaS **tenant** — hosts the platform, owns venues, RBAC, audit | Org switcher (admin shell only) |
| **Event** | Operational **games edition** for one **session year** (e.g. SUKMA 2026) | Primary admin context after selecting an event |
| **Edition year** | Nominal year of the games session — primary sort/filter key | `events.edition_year` (e.g. 2026) |
| **Cadence** | How often the games recur (annual, biennial, …) | On event or future `event_series` |
| **Participant** | **Competing unit** registered for an event | Event module — *Participants* |
| **Sport / Acara** | Discipline and category contested at the event | Event module — *Sports* |
| **Sport entry** | A participant's declaration to enter a sport (± category/division) | Event module — *Entries* or within *Participants* |
| **Team** | Roster-bearing unit for a sport entry (may be named after participant) | Event module — *Teams* |
| **Branch** | Optional org subdivision (campus, district) — can seed participants | Org settings, not org switcher |

**Rule:** Contingents (fakulti, negeri, negara) are **never** separate SaaS tenants. MSN, UTeM, and the host federation remain the **organization**; states/faculties/countries are **event participants**.

### 0.3 Scenario Mapping

| Step | SAF (UTeM) | SUKMA / MSSM | SEA Games |
|------|------------|--------------|-----------|
| Organization (tenant) | UTeM | MSN / host federation | OC / host NOC |
| Event (edition) | SAF 2026 | SUKMA Selangor 2026 | SEA Games 2025 |
| Edition year | 2026 | 2026 | 2025 |
| Cadence | Annual (typical) | Biennial | Biennial |
| Sports / Acara | Football, Badminton, … | Same structure | Same structure |
| Participant label | **Fakulti** | **Negeri** | **Negara** |
| Participant examples | FTK, FKE, FKM | Selangor, Johor, Sabah | Malaysia, Thailand, Singapore |
| Participant source | Org `branches` or manual | Manual / import | Manual / NOC list |
| Team | FTK Football (U21) | Selangor Football | Malaysia Badminton |
| Medal tally group | By fakulti | By negeri | By negara |

### 0.4 Anti-Patterns (Deprecated)

| Wrong model | Correct model |
|-------------|---------------|
| Each negeri as `Organization` in org switcher | Negeri as `event_participants` on the SUKMA event |
| MSN shown beside 16 states in tenant switcher | MSN = tenant; states = participants on event |
| Team `organization_id` = competing negeri | Team `event_participant_id` = negeri; `organization_id` = host tenant |
| Jump straight to athletes before participant roster | Register participants first, then sport entries, then rosters |

---

## 1. Core Module

### 1.1 Organizations

| ID | Requirement | Status |
|----|-------------|--------|
| CORE-ORG-01 | System Owner can create, update, deactivate organizations | Implemented |
| CORE-ORG-02 | Organization fields: name, slug, type, logo, timezone, locale, status | Partial (no logo UI) |
| CORE-ORG-03 | Organization types: federation, university, school, club, corporate, other | Implemented |
| CORE-ORG-04 | Org Admin can view and update own organization settings | Planned |
| CORE-ORG-05 | All queries auto-scoped by `organization_id` | Partial (middleware only) |

### 1.2 Branches

| ID | Requirement | Status |
|----|-------------|--------|
| CORE-BRN-01 | Organization can have multiple branches (state, district, campus) | Implemented |
| CORE-BRN-02 | Branch inherits org settings; can override timezone/locale | Planned |

### 1.3 Users & RBAC

| ID | Requirement | Status |
|----|-------------|--------|
| CORE-USR-01 | Users authenticate via email + password (session web, token API) | Implemented |
| CORE-USR-02 | User can belong to multiple organizations via pivot table | Implemented |
| CORE-USR-03 | Roles: system_owner, org_admin, event_organizer, sports_manager, team_manager, athlete, official, volunteer, media | Implemented |
| CORE-USR-04 | Permissions granular per module: view, create, update, delete, manage | Implemented |
| CORE-USR-05 | Binary `admin`/`user` role replaced by full RBAC | Implemented |
| CORE-USR-06 | Admin user CRUD with search and role filter | Implemented |
| CORE-USR-07 | MFA-ready (TOTP) authentication hooks | Planned |

### 1.4 Audit Logs

| ID | Requirement | Status |
|----|-------------|--------|
| CORE-AUD-01 | Log: actor, action, model, record ID, old/new JSON, IP, user agent, timestamp | Implemented |
| CORE-AUD-02 | Audit logs are append-only (no edit/delete) | Implemented |
| CORE-AUD-03 | Org Admin can view audit logs for own organization | Implemented |

---

## 2. Event Module

| ID | Requirement | Status |
|----|-------------|--------|
| EVT-01 | Create event with name, slug, dates, location, description | Implemented |
| EVT-02 | Event types: multi-sport games, single-sport tournament, league season, friendly | Implemented |
| EVT-03 | Event categories: age group, gender, level (school, university, elite) | Implemented |
| EVT-04 | Lifecycle: `draft` → `published` → `active` → `completed` → `archived` | Implemented |
| EVT-05 | Only published/active events visible on public portal | Planned |
| EVT-06 | Assign event organizers and sports managers | Implemented |
| EVT-07 | Event dashboard with status, participant count, schedule summary | Partial |
| EVT-08 | Event setup checklist reflecting canonical flow (steps 1–8) | Planned |
| EVT-09 | `participant_unit_label` on event (faculty / state / country) drives UI copy | Planned |
| EVT-10 | `edition_year` on every event for sorting and filtering by tahun | Planned |
| EVT-11 | `cadence` field: `annual`, `biennial`, `quadrennial`, `one_off` | Planned |
| EVT-12 | Optional `event_series_id` to group recurring editions (SUKMA 2024, 2026, …) | Planned |
| EVT-13 | Event list default sort: `edition_year` DESC, then `starts_at` | Planned |

### 2.1 Event Participants

| ID | Requirement | Status |
|----|-------------|--------|
| PAR-01 | Register competing units per event (`event_participants`) | Planned |
| PAR-02 | Participant types: `faculty`, `state`, `country`, `club`, `other` | Planned |
| PAR-03 | Optional link to org `branch_id` (SAF: fakulti from branches) | Planned |
| PAR-04 | CRUD + bulk import (CSV) of participants | **Implemented** |
| PAR-05 | Participant managers (team_manager role scoped to one participant) | Planned |
| PAR-06 | Do not create separate `organizations` for contingents | Planned |

### 2.2 Participant Sport Entries

| ID | Requirement | Status |
|----|-------------|--------|
| ENT-01 | Participant selects sports/categories/divisions to enter (`participant_sport_entries`) | Planned |
| ENT-02 | Entry workflow: draft → submitted → approved → rejected | Planned |
| ENT-03 | Approved entries gate team and athlete registration for that sport | Planned |
| ENT-04 | Event organizer can set entry deadlines per sport | Planned |
| ENT-05 | Entry list visible on participant profile and event dashboard | Planned |

---

## 3. Sports Module

| ID | Requirement | Status |
|----|-------------|--------|
| SPT-01 | Define sports per event (Football, Badminton, Swimming, Athletics, Esports, etc.) | Implemented |
| SPT-02 | Disciplines per sport (e.g., Swimming → freestyle, butterfly) | Implemented |
| SPT-03 | Categories: age, gender, weight class | Partial (gender + age; weight columns ready) |
| SPT-04 | Divisions: Open, U-18, U-21, etc. | Implemented |
| SPT-05 | Sport-specific rules configuration (JSON schema per sport) | Partial (JSON `rules` column + templates) |

---

## 4. Athlete Module

| ID | Requirement | Status |
|----|-------------|--------|
| ATH-01 | Athlete profile: name, DOB, gender, nationality, photo, ID number | Partial (photo deferred) |
| ATH-07 | Athlete linked to `event_participant_id` when registered for an event | Planned |
| ATH-02 | Link athlete to user account (optional) | Implemented |
| ATH-03 | Registration workflow: draft → submitted → verified → approved → rejected | Implemented |
| ATH-04 | Eligibility rules: age range, nationality, medical clearance flag | Partial (age, gender, medical) |
| ATH-05 | Participation history across events | Implemented |
| ATH-06 | Bulk import athletes (CSV) | Planned |

---

## 5. Team Module

| ID | Requirement | Status |
|----|-------------|--------|
| TEM-01 | Team belongs to event, sport, and **event participant** (competing unit) | Partial (`organization_id` today; `event_participant_id` planned) |
| TEM-02 | Team registration with approval workflow | Implemented |
| TEM-06 | Team created only when participant has approved sport entry | Planned |
| TEM-07 | Team name defaults from participant + sport (editable) | Planned |
| TEM-03 | Roster management: add/remove athletes | Implemented |
| TEM-04 | Assign coach and team manager | Implemented |
| TEM-05 | Transfer request between teams (approve/reject) | Planned |

---

## 6. Official Module

| ID | Requirement | Status |
|----|-------------|--------|
| OFF-01 | Official types: referee, judge, technical officer, timekeeper | Implemented |
| OFF-02 | Certification level and expiry tracking | Implemented |
| OFF-02a | Event registration with approval workflow | Implemented |
| OFF-03 | Assign officials to matches | Implemented |
| OFF-04 | Conflict detection for overlapping assignments | Planned |

---

## 7. Venue Module

| ID | Requirement | Status |
|----|-------------|--------|
| VEN-01 | Venue: name, address, capacity, timezone | Implemented |
| VEN-02 | Facilities: courts, fields, lanes, tracks with capacity | Implemented |
| VEN-03 | Availability calendar per facility | Planned |
| VEN-04 | Link venues to events | Implemented |
| VEN-05 | Link venues to sports per event | Implemented |

---

## 8. Scheduling Module

| ID | Requirement | Status |
|----|-------------|--------|
| SCH-01 | Competition formats: league, round robin, knockout, group stage | Implemented (4 formats) |
| SCH-02 | Manual fixture creation | Implemented |
| SCH-03 | Auto fixture generation from registered participants | Planned |
| SCH-04 | Venue allocation per match | Implemented |
| SCH-05 | Conflict detection: venue, official, athlete double-booking | Implemented |
| SCH-06 | Schedule calendar view (day/week) | Implemented (week view) |
| SCH-07 | AI-assisted scheduling (Phase 6) | Planned |

---

## 9. Competition Module

| ID | Requirement | Status |
|----|-------------|--------|
| CMP-01 | Create competition per sport + format | Implemented |
| CMP-02 | Group stage with standings table | Implemented |
| CMP-03 | Knockout bracket generation with seeding | Implemented |
| CMP-04 | Double elimination bracket | Implemented |
| CMP-05 | Swiss pairing rounds | Implemented |
| CMP-06 | Bracket visualization in admin UI | Implemented |

---

## 10. Results Module

| ID | Requirement | Status |
|----|-------------|--------|
| RES-01 | Score entry form per sport schema | Implemented |
| RES-02 | Result states: pending → confirmed → published | Implemented |
| RES-03 | Only assigned officials can enter scores | Implemented |
| RES-04 | Appeals: submit → under review → upheld/overturned | Implemented |
| RES-05 | Live results via WebSocket (Laravel Reverb) | Implemented |

---

## 11. Ranking Module

| ID | Requirement | Status |
|----|-------------|--------|
| RNK-01 | Team and athlete rankings per competition | Implemented |
| RNK-02 | Points table for league / round robin | Implemented |
| RNK-03 | Auto-recalculate on result confirmation | Implemented |
| RNK-04 | Tiebreaker rules configurable per sport | Implemented |

---

## 12. Medal Module

| ID | Requirement | Status |
|----|-------------|--------|
| MED-01 | Medal types: gold, silver, bronze | Implemented |
| MED-02 | Auto-assign medals from final standings | Implemented |
| MED-03 | Medal tally by country, organization, team | Implemented |
| MED-04 | Medal ceremony schedule (optional) | Implemented |

---

## 13. Accreditation Module

| ID | Requirement | Status |
|----|-------------|--------|
| ACC-01 | Pass types: athlete, official, volunteer, media | Planned |
| ACC-02 | QR code on pass (encode participant ID + event ID) | Planned |
| ACC-03 | PDF badge template with photo | Planned |
| ACC-04 | Gate validation endpoint (scan QR → verify) | Planned |
| ACC-05 | Bulk print accreditations | Planned |

---

## 14. Certificate Module

| ID | Requirement | Status |
|----|-------------|--------|
| CER-01 | Certificate types: participation, achievement, official | Planned |
| CER-02 | PDF generation with org branding | Planned |
| CER-03 | Bulk issuance after event completion | Planned |

---

## 15. Media Module

| ID | Requirement | Status |
|----|-------------|--------|
| MED-01 | Photo galleries per event | Planned |
| MED-02 | Video embed links (YouTube, Vimeo) | Planned |
| MED-03 | Press releases with publish date | Planned |

---

## 16. Announcement Module

| ID | Requirement | Status |
|----|-------------|--------|
| ANN-01 | News posts scoped to event or organization | Planned |
| ANN-02 | Email notifications for registration status, schedule changes | Planned |
| ANN-03 | In-app notification center | Planned |
| ANN-04 | Broadcast message to all event participants | Planned |

---

## 17. Reporting Module

| ID | Requirement | Status |
|----|-------------|--------|
| RPT-01 | Event summary report | Planned |
| RPT-02 | Participation report (by sport, category, org) | Planned |
| RPT-03 | Medal report | Planned |
| RPT-04 | Attendance report (accreditation scans) | Planned |
| RPT-05 | Export: PDF, Excel (XLSX), CSV | Planned |

---

## 18. Analytics Module

| ID | Requirement | Status |
|----|-------------|--------|
| ANA-01 | Participation trends over time | Planned |
| ANA-02 | Medal distribution analytics | Planned |
| ANA-03 | Event performance KPIs | Planned |
| ANA-04 | Sports popularity ranking | Planned |

---

## 19. Public Portal

| ID | Requirement | Status |
|----|-------------|--------|
| PUB-01 | Public event landing page (no auth) | Planned |
| PUB-02 | Live results feed | Planned |
| PUB-03 | Rankings and medal table views | Planned |
| PUB-04 | Public schedule / fixture view | Planned |
| PUB-05 | Athlete / team public profiles (opt-in) | Planned |
| PUB-06 | SEO metadata and Open Graph tags | Planned |
| PUB-07 | WCAG 2.1 AA compliance | Planned |

---

## 20. AI Module

| ID | Requirement | Status |
|----|-------------|--------|
| AI-01 | AI scheduling optimizer | Planned |
| AI-02 | Match outcome predictions | Planned |
| AI-03 | Performance insights dashboard | Planned |
| AI-04 | AI-generated event reports | Planned |
| AI-05 | Conversational assistant for organizers | Planned |

See [AI_GOVERNANCE.md](AI_GOVERNANCE.md) for governance requirements.

---

## 21. User Roles & Permissions Matrix

| Resource | System Owner | Org Admin | Event Organizer | Sports Manager | Team Manager | Athlete | Official | Public |
|----------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Organizations | CRUD | R (own) | — | — | — | — | — | — |
| Users | CRUD | CRUD (org) | R | R | R (team) | R (self) | R (self) | — |
| Events | CRUD | CRUD | CRUD (assigned) | R | R | R | R | R (published) |
| Sports | CRUD | CRUD | R | CRUD | R | R | R | R |
| Athletes | CRUD | CRUD | R | R | R (team) | CRUD (self) | R | R |
| Teams | CRUD | CRUD | R | R | CRUD (own) | R | R | R |
| Schedule | CRUD | CRUD | CRUD | CRUD | R | R | R | R |
| Results | CRUD | CRUD | CRUD | CRUD | R | R | C (assigned) | R (published) |
| Medals | CRUD | R | R | R | R | R | R | R |
| Accreditation | CRUD | CRUD | CRUD | R | R | R (self) | R (self) | — |
| Reports | CRUD | CRUD | R | R | — | — | — | — |
| Audit Logs | R | R (org) | — | — | — | — | — | — |

**Legend:** C = Create only for assigned resources, R = Read, CRUD = Full access.

Full permission enumeration in [SECURITY.md](SECURITY.md).

---

## 22. Related Documents

| Document | Link |
|----------|------|
| PRD | [PRD.md](PRD.md) |
| BRD | [BRD.md](BRD.md) |
| Roadmap | [ROADMAP.md](ROADMAP.md) |
| Database | [DATABASE.md](DATABASE.md) |
| API | [API.md](API.md) |
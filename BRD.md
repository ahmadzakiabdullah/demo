# Business Requirement Document (BRD)

# SportOS

**The Operating System for Sports Management**

| Pilot | University sports carnival (Malaysia) |
| Default timezone | `Asia/Kuala_Lumpur` |
| Documentation index | [DOCUMENTATION.md](DOCUMENTATION.md) |

---

## 1. Document Purpose

This BRD defines the business needs, stakeholders, processes, and success criteria for SportOS — an enterprise sports management platform serving organizations at all competitive levels.

---

## 2. Business Context

### 2.1 Background

Sports organizations manage complex operations: registrations, scheduling, officiating, results, medals, accreditation, and public communication. Most rely on manual processes and disconnected tools, leading to errors, delays, and poor spectator experience.

### 2.2 Business Opportunity

A unified SaaS platform reduces operational cost, improves data accuracy, enables real-time public engagement, and scales from local tournaments to international games — creating recurring revenue through organization subscriptions and event-based licensing.

### 2.3 Strategic Alignment

| Objective | How SportOS Delivers |
|-----------|---------------------|
| Digital transformation of sports admin | End-to-end digital workflows |
| Data-driven sports governance | Analytics, audit logs, reports |
| Athlete-centric experience | Self-service registration and history |
| Public engagement | Live results portal |
| Future innovation | AI-ready architecture |

### 2.4 Pilot Context (Malaysia)

Initial deployment targets **Malaysian university and school sports** before national/international scale:

| Level | Example | Priority |
|-------|---------|----------|
| University | UTeM sports carnival, inter-faculty games | **Primary pilot** |
| School | MSSM, district sports day | Secondary |
| State / national | State championships, Sukma | Future |
| International | SEA Games, Olympics | Design reference only |

This phased rollout reduces risk and validates the platform with real events before scaling.

---

## 3. Stakeholders

| Stakeholder | Role | Interest |
|-------------|------|----------|
| System Owner / Platform Operator | SaaS provider | Revenue, uptime, compliance |
| Sports Federation / Association | Paying customer | Event management efficiency |
| Event Organizer | End user | Smooth competition execution |
| Athletes & Teams | Participants | Easy registration, accurate results |
| Officials | Competition integrity | Clear assignments, score tools |
| Government / University Sports Body | Oversight | Reports, participation data |
| Media & Sponsors | Visibility | Live data, galleries |
| Public / Spectators | Engagement | Results, rankings, schedules |
| IT / DevOps | Operations | Deployability, monitoring |

---

## 4. Business Objectives

| ID | Objective | KPI |
|----|-----------|-----|
| BO-01 | Reduce event setup time | 50% faster vs manual baseline |
| BO-02 | Eliminate manual medal tally errors | 100% auto-calculation from confirmed results |
| BO-03 | Enable multi-organization tenancy | Support 1,000+ organizations on one instance |
| BO-04 | Provide real-time public results | Results visible within 60s of confirmation |
| BO-05 | Ensure regulatory compliance | Full audit trail for all data changes |
| BO-06 | Support mobile access | API coverage for all core workflows |
| BO-07 | Generate operational reports on demand | PDF/Excel export in < 30 seconds |

---

## 5. Business Processes

### 5.1 Organization Onboarding

```
System Owner creates organization
    → Org Admin invited
    → Org Admin configures branches, users, roles
    → Organization ready for events
```

### 5.2 Event Lifecycle

```
Draft event created
    → Sports & venues configured
    → Registration opens
    → Registrations verified & approved
    → Schedule generated
    → Event published (public visibility)
    → Competition runs (results entered)
    → Event completed → reports & certificates issued
    → Event archived
```

### 5.3 Competition & Results

```
Draw / fixture generation
    → Matches scheduled (venue + officials)
    → Results entered by officials
    → Results validated by sports manager
    → Rankings & medals auto-updated
    → Public portal updated
```

### 5.4 Accreditation

```
Participant approved
    → Accreditation pass generated (QR)
    → Pass printed / digital delivery
    → Gate scan at venue entry
```

---

## 6. Business Rules

| ID | Rule |
|----|------|
| BR-01 | Each organization has isolated data; no cross-tenant access |
| BR-02 | A user may belong to multiple organizations with different roles |
| BR-03 | Event data is scoped to its parent organization |
| BR-04 | Only verified registrations may participate in competitions |
| BR-05 | Results must be confirmed before affecting rankings or medals |
| BR-06 | An official cannot be assigned to overlapping matches without override |
| BR-07 | Medal counts derive only from published, confirmed results |
| BR-08 | Deleted users retain audit history (soft delete or anonymize) |
| BR-09 | Public users see only published event data |
| BR-10 | System Owner is the only role that can create organizations |

---

## 7. Functional Business Requirements

| ID | Requirement | Priority | Phase |
|----|-------------|----------|-------|
| FBR-01 | Manage organizations and branches | High | 1 |
| FBR-02 | Role-based access control per organization | High | 1 |
| FBR-03 | Create and manage multi-sport events | High | 1 |
| FBR-04 | Register athletes and teams with verification | High | 2 |
| FBR-05 | Manage venues and facilities | High | 2 |
| FBR-06 | Generate competition schedules | High | 2 |
| FBR-07 | Support multiple competition formats | High | 3 |
| FBR-08 | Enter and validate match results | High | 3 |
| FBR-09 | Calculate rankings and medal tables | High | 3 |
| FBR-10 | Issue QR accreditations | Medium | 4 |
| FBR-11 | Generate participation certificates (PDF) | Medium | 4 |
| FBR-12 | Export operational reports | Medium | 4 |
| FBR-13 | Public results and rankings portal | High | 5 |
| FBR-14 | AI-assisted scheduling | Low | 6 |

---

## 8. Non-Functional Business Requirements

| ID | Requirement |
|----|-------------|
| NBR-01 | Platform available during live events (99.9% uptime) |
| NBR-02 | Data encrypted in transit (HTTPS) and at rest for sensitive fields |
| NBR-03 | Compliance with PDPA / GDPR principles for personal data |
| NBR-04 | Support for Malaysia timezone and locale as default |
| NBR-05 | Multi-language support for public portal |
| NBR-06 | Accessibility compliance for public-facing pages |

---

## 9. Constraints & Assumptions

### Constraints

- Initial deployment on VPS / cloud (not on-premise only)
- MySQL as primary database
- shadcn/ui as sole UI component library
- Laravel as backend framework

### Assumptions

- Organizations have internet access during events
- Officials have devices for score entry (web or mobile via API)
- System Owner manages tenant billing externally (v1)

---

## 10. Out of Scope (Business)

- Payment processing and ticketing
- Live video streaming
- Equipment / inventory management
- Transportation and accommodation booking
- Anti-doping sample management

---

## 11. Success Metrics

| Metric | Target (Year 1 post-launch) |
|--------|----------------------------|
| Organizations onboarded | 10+ |
| Events managed | 50+ |
| Athlete registrations processed | 10,000+ |
| API uptime | 99.9% |
| Average event setup time | < 2 hours for standard tournament |
| Customer satisfaction (organizers) | ≥ 4.0 / 5.0 |

---

## 12. Related Documents

| Document | Link |
|----------|------|
| Product Requirements | [PRD.md](PRD.md) |
| Functional Specification | [FUNCTIONAL_SPEC.md](FUNCTIONAL_SPEC.md) |
| Development Roadmap | [ROADMAP.md](ROADMAP.md) |
| Security Guidelines | [SECURITY.md](SECURITY.md) |

---

## 13. Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-06-08 | SportOS Team | Initial BRD for SportOS platform |
# SUKMA Selangor 2026 — Demo Dataset

Realistic seed data for **SportOS** based on SUKMA Malaysia structure.

## Run

```powershell
# First run (or after migrate:fresh)
php artisan db:seed --class=Sukma2026Seeder

# Re-generate (purge previous SUKMA demo data)
$env:SUKMA_SEED_FORCE = "true"
php artisan db:seed --class=Sukma2026Seeder
```

## Output files

| File | Description |
|------|-------------|
| `contingents.json` | 16 Malaysian contingents (states / WP) |
| `sports.json` | 32 sports with event catalog |
| `venues.json` | 27 Selangor / Klang Valley venues |
| `planned_modules.json` | Accommodation, transport, accreditation, volunteers (not yet in DB schema) |
| `summary.json` | Generated statistics after seeding |
| `samples/athletes.csv` | Sample athlete export (50 rows) |
| `samples/medal_tally.csv` | Medal table by contingent |
| `samples/insert_sample.sql` | Representative SQL INSERT statements |

## Schema mapping

| SUKMA concept | SportOS model / table |
|---------------|----------------------|
| Penganjur MSN | `Organization` (`msn`) |
| Kontinjen negeri | `Organization` (16 contingents) |
| Jawatankuasa | `Branch` |
| SUKMA 2026 | `Event` |
| Sukan | `Sport` |
| Acara | `SportDiscipline` → `SportCategory` → `SportDivision` |
| Venue / gelanggang | `Venue` → `Facility` |
| Acara ↔ venue | `event_venue`, `event_sport_venue` |
| Atlet | `Athlete` + `Registration` |
| Pasukan kontinjen | `Team` + `team_athlete` + `Registration` |
| Jurulatih | `User` (`coach_user_id` on `Team`) |
| Pegawai / pengadil | `Official` + `MatchOfficial` |
| Pertandingan | `Competition` → `Fixture` → `MatchGame` |
| Peserta perlawanan | `MatchParticipant` |
| Keputusan | `Result` |
| Bantahan | `ResultAppeal` |
| Kedudukan liga | `Ranking` |
| Pingat | `Medal` |
| Majlis pingat | `MedalCeremony` |
| Sukarelawan | `User` + `organization_user` (volunteer role) |
| Penginapan / bas / akreditasi | `planned_modules.json` only (Phase 4+) |

## Login samples

| Role | Email pattern | Password |
|------|---------------|----------|
| Coach | `coach.sukma2026.*@sportos.demo` | `password` |
| Official (record) | `official.sukma2026.*@sportos.demo` | — |
| Volunteer | `volunteer.sukma2026.*@sportos.demo` | `password` |
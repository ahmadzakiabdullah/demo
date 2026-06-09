-- SUKMA Selangor 2026 — sample INSERT statements (SportOS schema)
-- Run full dataset via: php artisan db:seed --class=Sukma2026Seeder

INSERT INTO organizations (name, slug, type, timezone, locale, status, created_at, updated_at)
VALUES ('Majlis Sukan Negara Malaysia', 'msn', 'federation', 'Asia/Kuala_Lumpur', 'ms', 'active', NOW(), NOW());

INSERT INTO organizations (name, slug, type, timezone, locale, status, created_at, updated_at)
VALUES ('Kontinjen Selangor', 'selangor', 'federation', 'Asia/Kuala_Lumpur', 'ms', 'active', NOW(), NOW());

INSERT INTO events (organization_id, event_type_id, event_category_id, name, slug, status, location, starts_at, ends_at, created_at, updated_at)
VALUES (1, 1, 3, 'SUKMA Selangor 2026', 'sukma-selangor-2026', 'active', 'Selangor, Malaysia', '2026-08-15 08:00:00', '2026-08-24 22:00:00', NOW(), NOW());

INSERT INTO venues (organization_id, name, slug, address, capacity, timezone, created_at, updated_at)
VALUES (1, 'Stadium Nasional Bukit Jalil', 'stadium-nasional-bukit-jalil', 'Jalan Stadium Nasional, Bukit Jalil', 87411, 'Asia/Kuala_Lumpur', NOW(), NOW());
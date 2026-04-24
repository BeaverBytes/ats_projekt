# Web-ATS – Applicant Tracking System

Webbasiertes Bewerbermanagement-System zur strukturierten Abwicklung eines Bewerbungsprozesses. Entwickelt als Universitätsprojekt in PHP ohne Framework, mit Fokus auf saubere Schichtentrennung, rollenbasierte Zugriffskontrolle und typische Web-Sicherheitsmaßnahmen.

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)
![Status](https://img.shields.io/badge/Status-Prototyp-orange)

---

## Überblick

Das ATS deckt drei Perspektiven ab:

- **Öffentlicher Bereich** — Bewerber:innen sehen offene Stellen und können sich mit PDF-Anhängen bewerben.
- **Recruiter-Bereich** — Recruiter:innen verwalten ihre eigenen Stellen, bearbeiten den Status eingehender Bewerbungen und hinterlegen interne Notizen.
- **Admin-Bereich** — systemweite Übersicht über alle Stellen und Bewerbungen.

Die Anwendung wurde bewusst ohne Framework umgesetzt, um Routing, Session-Handling, Zugriffskontrolle und Datenbankzugriffe explizit zu implementieren statt sie wegzudelegieren.

## Tech-Stack

| Schicht       | Technologie                        |
|---------------|------------------------------------|
| Backend       | PHP 8.2                            |
| Datenbank     | SQLite 3 (via PDO)                 |
| Frontend      | HTML + CSS, serverseitig gerendert |
| Server        | Apache (XAMPP)                     |
| Versionierung | Git                                |

Bewusst nicht verwendet: JavaScript-Frameworks, ORMs, externe PHP-Frameworks. Alle Abstraktionen sind im Backend selbst implementiert.

## Architektur

Zwei-Schichten-Aufbau:

- `public/` — öffentlich erreichbare Einstiegspunkte. Verantwortlich für Request-Handling, Auth-Prüfung, Input-Validierung, Aufruf der Logik-Module und Rendering. Enthält keine SQL-Statements.
- `src/` — Anwendungslogik. Gekapselte Funktionen für Auth, Datenzugriff und Geschäftslogik. Alle Datenbankzugriffe laufen ausschließlich hier über PDO-Prepared-Statements.

Dadurch bleiben Datenbankdetails und Zugriffsprüfungen an einem Ort, und die Einstiegspunkte sind dünn.

## Datenmodell

Fünf Entitäten, relationales Schema:

```
users  ──< jobs  ──< applications  ──< documents
  │                        │
  └──────< notes >─────────┘
```

Details in [`data/schema.sql`](data/schema.sql). Integritätsregeln sind zusätzlich in der Datenbank abgesichert (`FOREIGN KEY`, `CHECK`, `NOT NULL`), PHP prüft vorgelagert.

## Rollen- und Berechtigungskonzept

Drei Rollen mit unterschiedlichen Rechten:

| Funktion                       	  | Public  | Recruiter | Admin |
|-----------------------------------------|:-------:|:---------:|:-----:|
| Stellenliste ansehen          	  | ✔	    | ✔		| ✔	|
| Bewerbung einreichen          	  | ✔	    |	        |       |
| Stellen anlegen               	  |         | ✔ 	| ✔ 	|
| Eigene Stellen verwalten      	  |         | ✔		| ✔	|
| Bewerbungen der eigenen Stellen ansehen |         | ✔		| ✔	|
| Alle Stellen / Bewerbungen ansehen 	  |         |           | ✔ 	|
| Status & Notizen bearbeiten   	  |         | ✔ * 	| ✔ 	|

\* Recruiter nur auf Datensätze zu eigenen Stellen. Zugriffsprüfung erfolgt serverseitig in den Logik-Funktionen (keine Frontend-Gates).

## Sicherheitsmaßnahmen

| Risiko                     | Maßnahme 													|
|----------------------------|------------------------------------------------------------------------------------------------------------------|
| SQL-Injection              | PDO-Prepared-Statements, keine String-Konkatenation in Queries 							|
| XSS                        | Output-Escaping via `htmlspecialchars()` mit `ENT_QUOTES` 							|
| CSRF                       | Session-gebundene Tokens, Validierung mit `hash_equals()` auf authentifizierten POST-Formularen			|
| Passwort-Leak              | bcrypt über `password_hash()` / `password_verify()` 								|
| Session-Fixation           | `session_regenerate_id(true)` nach erfolgreichem Login 								|
| Clickjacking               | `X-Frame-Options: DENY` auf geschützten Views 									|
| MIME-Sniffing              | `X-Content-Type-Options: nosniff` 										|
| Zugriff auf fremde Daten   | Ownership-Prüfung über `jobs.created_by_user_id` in den Service-Funktionen 					|
| Datei-Upload               | Typ- und Größenprüfung, MIME-Check via `finfo`, zufällige Dateinamen, Speicherung außerhalb des Webverzeichnisses|
| Datei-Download             | Nur über serverseitigen Endpoint mit Auth- und Ownership-Prüfung; `basename()` verhindert Path-Traversal 	|

## Datenschutz

Das Projekt ist als Prototyp mit Bezug zur DSGVO konzipiert — keine vollständige DSGVO-Implementierung.

- **Aktive Einwilligung** als Pflichtfeld im Bewerbungsformular (Art. 6 Abs. 1 lit. a DSGVO). Zustimmung wird zusammen mit dem Bewerbungseingang gespeichert.
- **Datenminimierung**: nur bewerbungsrelevante Pflichtfelder (Name, E-Mail, Stelle); Telefon und Motivationsschreiben sind optional.
- **Zugriffsbeschränkung**: Bewerberdaten nur für zuständige Recruiter und Admin zugänglich, Upload-Verzeichnis außerhalb des öffentlichen Web-Roots.

Bewusst nicht umgesetzt: fristbasierte automatische Löschung, Versionierung der Einwilligungstexte, vollständiges Auskunfts-/Löschportal für Betroffene.

## Lokale Installation

### Voraussetzungen

- PHP 8.2 oder höher
- `pdo_sqlite`-Extension (in XAMPP standardmäßig aktiv)
- Git

### Setup

```bash
# 1. Repository klonen
git clone https://github.com/beaverbytes/ats_projekt.git
cd ats_projekt

# 2. Datenbank anlegen
php scripts/init_db.php

# 3. Admin- und Recruiter-Accounts anlegen
php scripts/seed_users.php

# 4. Optional: Demo-Stellen und Demo-Bewerbungen erzeugen
php scripts/seed_demo_data.php

# 5. Built-in PHP-Server starten (Alternative zu XAMPP)
php -S localhost:8000 -t public
```

Die Anwendung ist anschließend unter `http://localhost:8000/` erreichbar.

> **Hinweis zu XAMPP:** Wird das Projekt unter `htdocs/ats_projekt/` abgelegt und über `http://localhost/ats_projekt/public/` aufgerufen, sind `uploads/` und `data/` parallel zu `public/` nicht automatisch durch den DocumentRoot geschützt. Für eine realistische Trennung ist ein Apache-VirtualHost mit `DocumentRoot → public/` sinnvoll. Beim eingebauten PHP-Server (`-t public`) ist die Trennung automatisch gegeben.

### Demo-Zugänge

Nach `seed_users.php`:

| Rolle     | E-Mail                    | Passwort        |
|-----------|---------------------------|-----------------|
| Admin     | `admin@example.com`       | `admin123`      |
| Recruiter | `recruiter1@example.com`  | `recruiter123`  |
| Recruiter | `recruiter2@example.com`  | `recruiter123`  |

Nur für lokale Entwicklung. Die Passwörter sind bewusst schwach und stehen als Klartext im Seed-Skript — in einer Produktion würden Accounts über ein Admin-UI mit erzwungenem Passwort-Wechsel beim Erst-Login angelegt.

## Projektstruktur

```
ats_projekt/
├── data/                 SQLite-DB und Schema
│   ├── schema.sql        Tabellen, Constraints, Indizes
│   └── ats.sqlite        per init_db.php erzeugt (nicht im Repo)
├── public/               öffentlich erreichbare Einstiegspunkte
│   ├── index.php         Karriere-Startseite
│   ├── apply.php         Bewerbungsformular + Upload
│   ├── login.php / logout.php
│   ├── jobs/             Stellenübersicht und Detailansicht
│   ├── admin/            Admin-Dashboard
│   ├── recruiter/        Recruiter-Bereich (RBAC-geschützt)
│   └── assets/           CSS
├── src/                  Anwendungslogik (nicht öffentlich)
│   ├── auth.php          Auth, RBAC, CSRF
│   ├── config.php
│   ├── db.php            PDO-Verbindung
│   ├── jobs.php
│   ├── applications.php
│   ├── documents.php
│   ├── users.php
│   └── dashboard_stats.php
├── scripts/              CLI-Scripts (Init, Seed)
├── uploads/              hochgeladene PDFs (nicht im Repo)
└── .gitignore
```

## Validierung

Manuelle funktionale Tests während der Entwicklung, unter anderem:

- Login mit falschen Credentials → generische Fehlermeldung (keine User-Enumeration).
- Bewerbungsformular: Pflichtfeld-Validierung, E-Mail-Format, Dateianzahl und -größe, Einwilligung als Pflicht.
- Recruiter A versucht, Bewerbung von Recruiter B per direkter URL aufzurufen → 404 / Zugriff verweigert.
- Status- und Notiz-Änderungen persistieren und sind nach Reload sichtbar.

Keine automatisierten Tests vorhanden — siehe „Bekannte Einschränkungen".

## Bekannte Einschränkungen

- Keine automatisierten Tests (PHPUnit, Integration, E2E)
- Kein Rate-Limiting am Login oder am Bewerbungsformular
- Keine E-Mail-Benachrichtigungen (Eingangsbestätigung, Statusänderung)
- Keine UI zur Benutzerverwaltung — Accounts werden über Seed-Script angelegt
- Keine fristbasierte automatische Löschung von Bewerbungen
- SQLite skaliert nur für kleine Nutzerzahlen; produktiv wäre PostgreSQL oder MySQL angemessen
- Keine Audit-Logs für Statuswechsel oder Notiz-Änderungen

## Mögliche Weiterentwicklung

- Migration auf ein etabliertes Framework (Symfony, Laravel) mit bestehender Auth-, ORM- und Validierungsschicht
- Wechsel auf PostgreSQL/MySQL und Einführung eines Migration-Tools
- PHPUnit-Tests für `src/`-Funktionen, E2E-Tests für die kritischen User-Flows
- Admin-UI für Benutzer- und Recruiter-Verwaltung
- Audit-Log für nachvollziehbare Statusänderungen
- Fristbasiertes Löschkonzept für DSGVO-konforme Aufbewahrung
- CI/CD-Pipeline mit automatischer Test- und Deploy-Stufe

## Kontext

Entwickelt im Rahmen des Studiums an der **IU International University of Applied Sciences** im Kurs **Projekt - Einstieg in die Web-Programmierung**.
Zeitraum: Wintersemester 2025/26.

## Autor

**Alexander Morgan**

- GitHub: [@\BeaverBytes](https://github.com/beaverbytes)

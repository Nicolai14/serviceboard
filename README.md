# ServiceBoard

> Self-hosted Server- & Infrastruktur-Monitoring-Dashboard auf Basis von Laravel 13, Tailwind CSS und Alpine.js.

ServiceBoard überwacht deine Server per SSH (CPU, RAM, Disk, Load, Uptime), verwaltet Docker-Container, Dienste und Deployments, visualisiert die Projekt-Architektur als Baukasten und behält die Kosten im Blick — alles in einem aufgeräumten Dashboard mit Privat-/Geschäftlich-Workspaces.

## Screenshots

> Die Bilder liegen unter [`docs/screenshots/`](docs/screenshots/) — siehe die Anleitung dort zum Hinzufügen/Aktualisieren.

| Dashboard | Server-Detail |
|---|---|
| ![Dashboard](docs/screenshots/dashboard.png) | ![Server-Detail](docs/screenshots/server-detail.png) |

| Projekt-Workflows | Kostenübersicht |
|---|---|
| ![Projekt-Workflows](docs/screenshots/workflows.png) | ![Kostenübersicht](docs/screenshots/costs.png) |

## Features

- **Server-Übersicht** — Status, CPU, RAM, Disk, Uptime per SSH-Polling, inkl. 24h-Verlaufsdiagramm
- **SSH-Verbindungstest** — TCP-Reachability und Auth-Test direkt aus dem Dashboard
- **Docker-Monitoring** — Container-Status pro Server und globale Übersicht
- **Services** — Verwaltung von Diensten pro Server inkl. HTTP/TCP-Health-Checks mit Status-Tracking und Alerts
- **Deployments** — Git-Pull, Shell-Script oder Docker-Compose per SSH ausführen, mit Live-Log
- **Projekt-Workflows** — visueller Baukasten: App, Server, Docker-Container, Software, Domain, Dienst und Datenbank als Bausteine platzieren, benennen und verbinden, um den Projektaufbau abzubilden (pro Workspace)
- **Kosten** — automatische Kostenübersicht über alle Server und Domains plus eigene Posten, monatlich/jährlich
- **Cloudflare-Integration** — DNS-Einträge und Zonen-Status
- **Alerts** — Benachrichtigungen (u.a. Telegram) bei Ausfällen oder Schwellenwertüberschreitungen, mit pro Server konfigurierbaren CPU/RAM/Disk-Schwellwerten
- **Workspaces** — Trennung zwischen Privat (🏠) und Geschäftlich (💼)

## Stack

| Komponente | Version |
|---|---|
| PHP | 8.4 |
| Laravel | 13.x |
| MySQL | 8.4 |
| Redis | Alpine |
| Node.js | 22 |
| Tailwind CSS | 4.x |
| Alpine.js | 3.x |

## Lokale Entwicklung

```bash
cp .env.example .env
composer install
npm install

php artisan key:generate
php artisan migrate --seed

npm run dev
php artisan serve
```

## Deployment

Pushes auf `main` deployen automatisch via GitHub Actions auf den Produktionsserver (per SSH).

Die Pipeline läuft Tests, prüft Abhängigkeiten auf bekannte Schwachstellen, baut Assets und deployt:

```
push → test (paratest) → build-assets → lint → static-analysis (Larastan) → dependency-audit → deploy
```

### Voraussetzungen (Server)

- Docker + Docker Compose
- Node.js 22
- `.env` unter `/root/serviceboard/.env` (`APP_ENV=production`, `APP_DEBUG=false`)
- GitHub Secrets: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`

## SSH-Monitoring

ServiceBoard verbindet sich per SSH mit den verwalteten Servern und führt ein Shell-Script aus, das CPU, RAM, Disk, Load und Uptime in einem einzigen Durchlauf erfasst.

Empfohlener Setup: dedizierter `monitor`-User ohne Root-Rechte, mit SSH-Key-Auth. Docker-Metriken erfordern Mitgliedschaft in der `docker`-Gruppe. SSH-Zugangsdaten werden verschlüsselt gespeichert (`encrypted` cast) und nie in API-Antworten ausgegeben.

## Lizenz

[MIT](LICENSE)

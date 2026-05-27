# ServerFlow

Server-Monitoring Dashboard auf Basis von Laravel 13, Tailwind CSS und Alpine.js.

## Features

- **Server-Übersicht** — Status, CPU, RAM, Disk, Uptime per SSH-Polling
- **SSH-Verbindungstest** — TCP-Reachability und Auth-Test direkt aus dem Dashboard
- **Docker-Monitoring** — Container-Status pro Server und globale Übersicht
- **Cloudflare-Integration** — DNS-Einträge und Zonen-Status
- **Workspaces** — Trennung zwischen Privat (🏠) und Geschäftlich (💼)
- **Alerts** — Benachrichtigungen bei Ausfällen oder Schwellenwertüberschreitungen, mit pro Server konfigurierbaren CPU/RAM/Disk-Schwellwerten
- **Services** — Verwaltung von Diensten pro Server inkl. HTTP/TCP-Health-Checks mit Status-Tracking und Alerts
- **Deployments** — Git-Pull, Shell-Script oder Docker-Compose per SSH ausführen, mit Live-Log

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

Pushes auf `main` deployen automatisch via GitHub Actions auf den Hetzner-Server.

Die Pipeline läuft Tests, baut Assets und deployt per SSH:

```
push → test (paratest) → build-assets → lint → static-analysis (Larastan) → deploy
```

### Voraussetzungen (Server)

- Docker + Docker Compose
- Node.js 22
- `.env` unter `/root/serverflow/.env`
- GitHub Secrets: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`

## SSH-Monitoring

ServerFlow verbindet sich per SSH mit den verwalteten Servern und führt ein Shell-Script aus, das CPU, RAM, Disk, Load und Uptime in einem einzigen Durchlauf erfasst.

Empfohlener Setup: dedizierter `monitor`-User ohne Root-Rechte, mit SSH-Key-Auth. Docker-Metriken erfordern Mitgliedschaft in der `docker`-Gruppe.

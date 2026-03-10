# www.mrifqyabdallah.com

My personal website in an attempt to learn CI/CD. Built with Laravel + React via InertiaJS, containerized with FrankenPHP, and deployed on a VPS behind Cloudflare.

[![Deploy](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/deploy.yml/badge.svg)](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/deploy.yml)
[![Pest](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/pest.yml/badge.svg)](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/pest.yml)
[![PHPStan](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/phpstan.yml/badge.svg)](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/phpstan.yml)
[![Code style](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/code-style.yml/badge.svg)](https://github.com/mrifqyabdallah/mrifqyabdallah.com/actions/workflows/code-style.yml)

[![Docker](https://img.shields.io/badge/ghcr.io-mrifqyabdallah%2Fmrifqyabdallah.com-blue?logo=docker)](https://ghcr.io/mrifqyabdallah/mrifqyabdallah.com:latest)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=white)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org)

## Infrastructure

```
 Browser
    │
    ▼
┌─────────────────────┐
│     Cloudflare      │  DDoS protection · SSL/TLS · CDN
└─────────┬───────────┘
          │ HTTPS
          ▼
┌─────────────────────┐
│         VPS         │
│  ┌───────────────┐  │
│  │  FrankenPHP   │  │  HTTP server · PHP runtime · Caddy
│  │ classic mode  │  │
│  │  (app:8000)   │  │
│  └──────┬────────┘  │
│         │           │
│  ┌──────▼────────┐  │
│  │  PostgreSQL   │  │  Persistent storage
│  │  (db:5432)    │  │
│  └───────────────┘  │
└─────────────────────┘
```

| Layer | Technology |
|---|---|
| Frontend | React 19 + TypeScript + Inertia.js |
| Backend | Laravel + PHP |
| Server | FrankenPHP (Caddy + PHP-FPM in one) |
| Database | PostgreSQL |
| Container | Docker · Docker Compose |
| Registry | GitHub Container Registry (GHCR) |
| CI/CD | GitHub Actions |
| Proxy / CDN | Cloudflare |
| Host | VPS |

## CI/CD Pipeline

```
 git push
    │
    ▼
┌──────────────────────────────────────┐
│           GitHub Actions             │
│                                      │
│  CI ──► lint · test · build          │
│                                      │
│  CD ──► docker build                 │
│          └─► push → ghcr.io          │
│               └─► ssh deploy → VPS   │
│                    └─► docker pull   │
│                         └─► up -d    │
└──────────────────────────────────────┘
```

## Local Development

After cloning the repo, simply run:

```bash
make dev.init
```

See [DOCKER.md](DOCKER.md) for full Docker usage

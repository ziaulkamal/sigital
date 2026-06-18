<div align="center">

# E-Gov CRM

### Laravel + Tailwinds + Vue

A modern, fully-featured CRM UI Design System for government and enterprise applications.

Developed by **[Ziaul Kamal](https://github.com/ziaulkamal)**

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue-3.x-42b883?style=flat&logo=vue.js&logoColor=white)](https://vuejs.org)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-4.x-38bdf8?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178c6?style=flat&logo=typescript&logoColor=white)](https://typescriptlang.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-3.x-9553E9?style=flat)](https://inertiajs.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat)](LICENSE)

</div>

---

## Preview

<p align="center">
  <img src="./screenshoot.png" alt="E-Gov CRM Preview" width="100%">
</p>

---

## Overview

**E-Gov CRM** adalah starter kit UI yang dibangun di atas stack modern Laravel + Inertia.js + Vue 3 + TailwindCSS v4. Menyediakan sistem komponen lengkap, template halaman, dan layout shell yang dirancang untuk aplikasi CRM, dashboard administrasi, dan layanan berbasis pemerintahan digital.

Semua komponen dibangun dari scratch menggunakan Scoped CSS + CSS Custom Properties — tanpa dependensi UI library eksternal seperti Element Plus atau Vuetify.

---

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Backend | Laravel | 13.x |
| Frontend Bridge | Inertia.js | 3.x |
| UI Framework | Vue 3 (Composition API) | 3.5.x |
| Language | TypeScript (strict mode) | 5.x |
| Styling | TailwindCSS v4 (CSS-first) | 4.x |
| Build Tool | Vite | 8.x |
| Icons | @lucide/vue (tree-shakeable) | 1.x |
| Charts | Chart.js + vue-chartjs | 4.x |
| Fonts | DM Sans Variable, JetBrains Mono, Geist | — |

---

## Fitur Utama

- **Dark Mode** — class-based via `.dark` pada `<html>`, disimpan ke `localStorage`, aurora animation di sidebar
- **Sidebar Collapsible** — state persisten antar navigasi halaman, ikon-only mode di tablet
- **Active Nav State** — auto-detect dari URL aktif via `usePage()`, dropdown auto-open untuk rute yang aktif
- **Topbar** — search bar, theme toggle, notifikasi, user dropdown dengan animasi
- **Responsive** — mobile drawer overlay, tablet icon-only, desktop full sidebar
- **Icon Gallery** — 220+ ikon dari `@lucide/vue` dengan hover tooltip menampilkan kode import
- **TypeScript Strict** — semua file `.vue`, composable, dan data menggunakan TypeScript penuh

---

## Komponen

### Foundation (Batch 01)
- Design tokens via `@theme` — color, radius, shadow, font, spacing
- Dark mode CSS custom properties
- `BaseLayout.vue` — shell layout dengan sidebar + topbar
- `AuthLayout.vue` — layout terpusat untuk halaman autentikasi
- `useTheme.ts` — composable dark mode dengan `localStorage`

### Core UI (Batch 02) — 23 Komponen

| Kategori | Komponen |
|---|---|
| Form | `AppButton`, `AppInput`, `AppTextarea`, `AppSelect`, `AppCheckbox`, `AppRadio`, `AppToggle` |
| Display | `AppBadge`, `AppAvatar`, `AppSpinner`, `AppSkeleton`, `AppDivider`, `AppProgressBar` |
| Feedback | `AppToast`, `AppAlert`, `AppModal`, `AppDrawer` |
| Overlay | `AppTooltip`, `AppPopover`, `AppDropdown`, `AppContextMenu` |
| Structure | `AppTabs`, `AppAccordion`, `AppBreadcrumb`, `AppCard`, `AppEmptyState` |

### Dashboard Widgets (Batch 03)
`KpiCard` · `RevenueChart` · `DealFunnelChart` · `ActivityFeed` · `QuickActionBar` · `RecentContactsWidget`

### Fitur Kompleks (Batch 04) — 14 Komponen
`DataTable` · `AppPagination` · `AppDatePicker` · `FileDropzone` · `WizardStepper` · `WizardForm` · `KanbanBoard` · `KanbanCard` · `MailLayout` · `MailListItem` · `MailCompose` · `ChatLayout` · `ChatBubble` · `ChatInput`

---

## Halaman

| Route | Halaman | Deskripsi |
|---|---|---|
| `/` | Dashboard | KPI cards, revenue chart, deal pipeline, activity feed |
| `/contacts` | Contacts | DataTable dengan filter, bulk select, status badge |
| `/contacts/new` | New Contact | Wizard form 4 langkah |
| `/contacts/:id` | Contact Detail | Profil, deals, timeline aktivitas |
| `/kanban` | Kanban | Drag-and-drop CRM pipeline (5 kolom) |
| `/mail` | Mail | 3-panel inbox: sidebar · list · thread |
| `/chat` | Chat | Chat interface dengan date divider & read receipt |
| `/settings` | Settings | Profile, notifikasi, keamanan, appearance |
| `/icons` | Icon Gallery | 220+ ikon dengan hover tooltip kode import |
| `/blocks` | Block Sections | Stat card, pricing, team, feature highlight, alert, empty state |
| `/demo/datatable` | DataTable Demo | Sortable, filterable, paginated |
| `/demo/datepicker` | DatePicker Demo | Single, range, constrained |
| `/demo/dropzone` | Dropzone Demo | Upload dengan simulasi progress |
| `/demo/wizard` | Wizard Demo | Multi-step form dengan review step |
| `/login` | Login | Email + password, social buttons |
| `/register` | Register | Password strength meter |
| `/forgot-password` | Forgot Password | Form + sent confirmation state |
| `/404` | 404 Error | Gradient text, blob dekoratif |
| `/500` | 500 Error | Server error dengan random error ID |
| `/ui` | UI Showcase | Preview live semua komponen Batch 02 |

---

## Struktur Proyek

```
resources/
├── css/
│   └── app.css                   # Design tokens (@theme), dark mode vars
├── js/
│   ├── app.ts                    # Entry point Inertia + Vue
│   ├── Composables/
│   │   ├── useTheme.ts           # Dark mode toggle + localStorage
│   │   └── useToast.ts           # Global toast state
│   ├── Components/
│   │   ├── App/                  # Semua komponen UI reusable
│   │   └── Dashboard/            # Widget khusus dashboard
│   ├── data/
│   │   └── navGroups.ts          # Data navigasi sidebar (shared)
│   ├── Layouts/
│   │   ├── BaseLayout.vue        # Layout utama (sidebar + topbar)
│   │   └── AuthLayout.vue        # Layout halaman auth
│   └── Pages/                    # Komponen halaman Inertia
routes/
└── web.php                       # Semua route aplikasi
```

---

## Instalasi

### Requirements
- PHP 8.2+
- Node.js 20+
- Composer 2.x

### Setup

```bash
# Clone repository
git clone https://github.com/ziaulkamal/starter-laravel-tailwindcss.git
cd starter-laravel-tailwindcss

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Konfigurasi environment
cp .env.example .env
php artisan key:generate

# Jalankan development server
php artisan serve

# Di terminal terpisah
npm run dev
```

### Production Build

```bash
npm run build
```

---

## Konvensi Kode

- **Dark mode:** class-based via `.dark` pada `<html>`, dikelola `useTheme.ts`
- **Warna:** CSS custom properties (`--color-primary`, `--color-surface`, dst.)
- **Penamaan komponen:** PascalCase `.vue`
- **Composables:** prefix `use`, ekstensi `.ts`
- **Styling:** Scoped CSS + CSS custom properties — tidak ada dynamic Tailwind string di computed
- **Icons:** tree-shaken dari `@lucide/vue` — social/brand icon tidak tersedia
- **Props:** selalu `type` + `default`, diketik dengan TypeScript runtime API

---

## Catatan Pengembangan

Proyek ini dikembangkan dalam 5 batch berurutan:

1. **Batch 01** — Design System & Foundation
2. **Batch 02** — Core UI Components (23 komponen)
3. **Batch 03** — Navigation Shell & Dashboard
4. **Batch 04** — Complex Feature Components (14 komponen)
5. **Batch 05** — Sample Pages & Polish (20 halaman)

---

## Lisensi

MIT — Bebas digunakan untuk proyek personal maupun komersial.

---

<div align="center">

**E-Gov CRM** &nbsp;·&nbsp; Laravel + Tailwinds + Vue

Dikembangkan oleh **[Ziaul Kamal](https://github.com/ziaulkamal)**

</div>

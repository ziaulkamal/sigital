// resources/js/data/navGroups.ts
// Navigasi sidebar SIGITAL. `roles` opsional membatasi item per peran (Admin/Operator).
import {
    LayoutDashboard, Settings,
    CalendarIcon, AwardIcon, PenLineIcon, ShieldCheckIcon, UserCheckIcon, LayoutTemplateIcon,
} from '@lucide/vue';

export interface NavItem {
    label:      string;
    icon?:      unknown;
    href?:      string;
    badge?:     string | number;
    badgeColor?: string;
    subtitle?:  string;
    active?:    boolean;
    children?:  NavItem[];
    roles?:     string[];   // bila diisi, hanya peran ini yang melihat item
}

export interface NavGroup {
    label?:    string;
    subtitle?: string;
    color?:    string;
    items:     NavItem[];
}

export const navGroups: NavGroup[] = [
    {
        label: '',
        items: [
            { label: 'Dashboard', icon: LayoutDashboard, href: '/dashboard' },
        ],
    },
    {
        label:    'Sertifikat',
        subtitle: 'Tata kelola & penerbitan',
        color:    '#2563eb',
        items: [
            { label: 'Acara',            icon: CalendarIcon, href: '/events' },
            { label: 'Arsip Sertifikat', icon: AwardIcon,    href: '/certificates' },
        ],
    },
    {
        label: 'Administrasi',
        color: '#7c3aed',
        items: [
            { label: 'Penanda Tangan', icon: PenLineIcon,         href: '/signatories', roles: ['Admin', 'SuperAdmin'] },
            { label: 'Template',       icon: LayoutTemplateIcon,  href: '/templates',   roles: ['Admin', 'SuperAdmin'] },
            { label: 'Log Audit',      icon: ShieldCheckIcon,     href: '/audit',       roles: ['Admin', 'SuperAdmin'] },
        ],
    },
    {
        label: 'SuperAdmin',
        color: '#0ea5e9',
        items: [
            { label: 'Persetujuan', icon: UserCheckIcon, href: '/approvals', roles: ['SuperAdmin'] },
        ],
    },
    {
        label: 'Sistem',
        color: '#64748b',
        items: [
            { label: 'Pengaturan', icon: Settings, href: '/settings' },
        ],
    },
];

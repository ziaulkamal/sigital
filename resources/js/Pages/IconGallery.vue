<template>
    <BaseLayout :nav-groups="navGroups">
        <div class="page">
            <div class="page__header">
                <div>
                    <h1 class="page__title">Icon Gallery</h1>
                    <p class="page__sub">{{ filtered.length }} icons from @lucide/vue — click to copy import</p>
                </div>
            </div>

            <!-- Search + size -->
            <div class="ig__toolbar">
                <div class="ig__search-wrap">
                    <SearchIcon :size="14" class="ig__search-icon" />
                    <input v-model="query" class="ig__search" placeholder="Search icons…" />
                    <button v-if="query" class="ig__clear" @click="query = ''"><XIcon :size="12" /></button>
                </div>
                <div class="ig__size-toggle">
                    <button
                        v-for="sz in [16, 20, 24]"
                        :key="sz"
                        class="ig__size-btn"
                        :class="{ 'ig__size-btn--active': iconSize === sz }"
                        @click="iconSize = sz"
                    >{{ sz }}px</button>
                </div>
            </div>

            <!-- Grid -->
            <TransitionGroup name="ig-item" tag="div" class="ig__grid">
                <button
                    v-for="icon in filtered"
                    :key="icon.name"
                    class="ig__item"
                    :class="{ 'ig__item--copied': copied === icon.name }"
                    @click="copy(icon.name)"
                    @mouseenter="showTooltip($event, icon.name)"
                    @mouseleave="hideTooltip"
                >
                    <component :is="icon.component" :size="iconSize" />
                    <span class="ig__item-name">{{ icon.name }}</span>
                    <span v-if="copied === icon.name" class="ig__copied-badge">Copied!</span>
                </button>
                <div v-if="filtered.length === 0" key="empty" class="ig__empty">
                    No icons match "{{ query }}"
                </div>
            </TransitionGroup>
        </div>

        <!-- Hover tooltip (teleported to body for correct positioning) -->
        <Teleport to="body">
            <div
                v-if="tooltip.visible"
                class="ig__tooltip"
                :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px' }"
            >
                <span class="ig__tooltip-import">import &#123;</span>
                <span class="ig__tooltip-name">{{ tooltip.name }}Icon</span>
                <span class="ig__tooltip-import">&#125; from '@lucide/vue'</span>
            </div>
        </Teleport>
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { navGroups } from '@/data/navGroups';
import BaseLayout from '@/Layouts/BaseLayout.vue';
import {
    SearchIcon, XIcon,
    // People & auth
    HomeIcon, UsersIcon, UserIcon, UserCheckIcon, UserXIcon, UserMinusIcon, UserPlusIcon,
    LogOutIcon, LogInIcon, ContactIcon,
    // Communication
    MailIcon, PhoneIcon, MessageSquareIcon, MessageCircleIcon, BellIcon, BellOffIcon,
    SendIcon, InboxIcon, ArchiveIcon, ReplyIcon, PhoneCallIcon, PhoneOffIcon,
    PhoneIncomingIcon, PhoneOutgoingIcon, PhoneMissedIcon, RssIcon,
    // Actions
    EditIcon, TrashIcon, PlusIcon, MinusIcon, CopyIcon, ClipboardIcon, ScissorsIcon,
    RefreshCwIcon, RotateCcwIcon, RotateCwIcon, DownloadIcon, UploadIcon, ShareIcon,
    // Status / feedback
    CheckIcon, CheckCircleIcon, CheckSquareIcon, XCircleIcon, XSquareIcon,
    AlertTriangleIcon, AlertCircleIcon, AlertOctagonIcon, InfoIcon, HelpCircleIcon,
    MinusCircleIcon, PlusCircleIcon,
    // Navigation arrows
    ArrowLeftIcon, ArrowRightIcon, ArrowUpIcon, ArrowDownIcon,
    ArrowUpRightIcon, ArrowDownLeftIcon, ArrowUpLeftIcon, ArrowDownRightIcon,
    ChevronLeftIcon, ChevronRightIcon, ChevronUpIcon, ChevronDownIcon,
    ChevronsLeftIcon, ChevronsRightIcon, ChevronsUpIcon, ChevronsDownIcon,
    CornerUpLeftIcon, CornerUpRightIcon, MoveIcon, ExternalLinkIcon, LinkIcon,
    // Favorites / social
    StarIcon, StarOffIcon, HeartIcon, BookmarkIcon,
    // Files & folders
    FileIcon, FileTextIcon, FilePlusIcon, FileMinusIcon, FileCheckIcon, FileXIcon,
    FileCodeIcon, FileImageIcon, FileVideoIcon,
    FolderIcon, FolderOpenIcon, FolderPlusIcon, FolderMinusIcon,
    // Calendar & time
    CalendarIcon, ClockIcon, TimerIcon, AlarmClockIcon, WatchIcon,
    // Location
    MapPinIcon, MapIcon, NavigationIcon, LocateIcon, CompassIcon,
    // Media controls
    PlayIcon, PauseIcon, StopCircleIcon, SkipBackIcon, SkipForwardIcon,
    RewindIcon, FastForwardIcon, Volume1Icon, Volume2Icon, VolumeXIcon,
    MicIcon, MicOffIcon, CameraIcon, CameraOffIcon, RadioIcon, TvIcon,
    // Images & design
    ImageIcon, VideoIcon, MusicIcon, CropIcon, FlipHorizontalIcon, FlipVerticalIcon,
    PenToolIcon, PencilIcon, HighlighterIcon, EraserIcon, RulerIcon,
    // Typography
    TypeIcon, BoldIcon, ItalicIcon, UnderlineIcon, StrikethroughIcon,
    AlignLeftIcon, AlignCenterIcon, AlignRightIcon, AlignJustifyIcon,
    // Code & tech
    CodeIcon, TerminalIcon, DatabaseIcon, ServerIcon, CpuIcon, HardDriveIcon,
    GitBranchIcon, GitCommitIcon, GitMergeIcon, GitPullRequestIcon,
    // Cloud & connectivity
    CloudIcon, CloudUploadIcon, CloudDownloadIcon, WifiIcon, WifiOffIcon,
    BluetoothIcon, BluetoothOffIcon,
    // Security
    LockIcon, UnlockIcon, KeyIcon, ShieldIcon, ShieldCheckIcon, ShieldOffIcon,
    EyeIcon, EyeOffIcon, FingerprintIcon,
    // Charts & data
    BarChart3Icon, BarChart2Icon, PieChartIcon, LineChartIcon, TrendingUpIcon, TrendingDownIcon,
    ActivityIcon,
    // Layout & UI
    LayersIcon, GridIcon, ListIcon, LayoutIcon, LayoutDashboardIcon, SidebarIcon,
    PanelLeftIcon, PanelRightIcon, ColumnsIcon, TableIcon, MaximizeIcon, MinimizeIcon,
    MoreHorizontalIcon, MoreVerticalIcon, FilterIcon, SortAscIcon, SortDescIcon,
    SlashIcon, HashIcon, AtSignIcon,
    // Commerce
    ShoppingCartIcon, ShoppingBagIcon, PackageIcon, CreditCardIcon, DollarSignIcon,
    ReceiptIcon, TagIcon, TicketIcon, PercentIcon,
    // Business
    BuildingIcon, Building2Icon, BriefcaseIcon, GraduationCapIcon, AwardIcon,
    BadgeIcon, ClipboardListIcon, ClipboardCheckIcon, BookOpenIcon, BookIcon,
    // Devices
    MonitorIcon, SmartphoneIcon, TabletIcon, PrinterIcon,
    // Weather & nature
    SunIcon, MoonIcon, CloudRainIcon, CloudSnowIcon, CloudLightningIcon,
    WindIcon, DropletsIcon, ThermometerIcon, SnowflakeIcon, FlameIcon,
    ZapIcon, LeafIcon,
    // Misc
    SparklesIcon, Wand2Icon, GlobeIcon, CrosshairIcon, TargetIcon,
    MousePointerIcon, HandIcon, CoffeeIcon,
    SettingsIcon, SettingsIcon as SettingsIcon2, WrenchIcon, SlidersIcon,
} from '@lucide/vue';

const allIcons = [
    // People & auth
    { name: 'Home',          component: HomeIcon },
    { name: 'Users',         component: UsersIcon },
    { name: 'User',          component: UserIcon },
    { name: 'UserCheck',     component: UserCheckIcon },
    { name: 'UserX',         component: UserXIcon },
    { name: 'UserMinus',     component: UserMinusIcon },
    { name: 'UserPlus',      component: UserPlusIcon },
    { name: 'LogOut',        component: LogOutIcon },
    { name: 'LogIn',         component: LogInIcon },
    { name: 'Contact',       component: ContactIcon },
    // Communication
    { name: 'Mail',          component: MailIcon },
    { name: 'Phone',         component: PhoneIcon },
    { name: 'MessageSquare', component: MessageSquareIcon },
    { name: 'MessageCircle', component: MessageCircleIcon },
    { name: 'Bell',          component: BellIcon },
    { name: 'BellOff',       component: BellOffIcon },
    { name: 'Send',          component: SendIcon },
    { name: 'Inbox',         component: InboxIcon },
    { name: 'Archive',       component: ArchiveIcon },
    { name: 'Reply',         component: ReplyIcon },
    { name: 'PhoneCall',     component: PhoneCallIcon },
    { name: 'PhoneOff',      component: PhoneOffIcon },
    { name: 'PhoneIncoming', component: PhoneIncomingIcon },
    { name: 'PhoneOutgoing', component: PhoneOutgoingIcon },
    { name: 'PhoneMissed',   component: PhoneMissedIcon },
    { name: 'Rss',           component: RssIcon },
    // Actions
    { name: 'Edit',          component: EditIcon },
    { name: 'Trash',         component: TrashIcon },
    { name: 'Plus',          component: PlusIcon },
    { name: 'Minus',         component: MinusIcon },
    { name: 'Copy',          component: CopyIcon },
    { name: 'Clipboard',     component: ClipboardIcon },
    { name: 'Scissors',      component: ScissorsIcon },
    { name: 'RefreshCw',     component: RefreshCwIcon },
    { name: 'RotateCcw',     component: RotateCcwIcon },
    { name: 'RotateCw',      component: RotateCwIcon },
    { name: 'Download',      component: DownloadIcon },
    { name: 'Upload',        component: UploadIcon },
    { name: 'Share',         component: ShareIcon },
    // Status
    { name: 'Check',         component: CheckIcon },
    { name: 'CheckCircle',   component: CheckCircleIcon },
    { name: 'CheckSquare',   component: CheckSquareIcon },
    { name: 'XCircle',       component: XCircleIcon },
    { name: 'XSquare',       component: XSquareIcon },
    { name: 'AlertTriangle', component: AlertTriangleIcon },
    { name: 'AlertCircle',   component: AlertCircleIcon },
    { name: 'AlertOctagon',  component: AlertOctagonIcon },
    { name: 'Info',          component: InfoIcon },
    { name: 'HelpCircle',    component: HelpCircleIcon },
    { name: 'MinusCircle',   component: MinusCircleIcon },
    { name: 'PlusCircle',    component: PlusCircleIcon },
    // Arrows & navigation
    { name: 'ArrowLeft',       component: ArrowLeftIcon },
    { name: 'ArrowRight',      component: ArrowRightIcon },
    { name: 'ArrowUp',         component: ArrowUpIcon },
    { name: 'ArrowDown',       component: ArrowDownIcon },
    { name: 'ArrowUpRight',    component: ArrowUpRightIcon },
    { name: 'ArrowDownLeft',   component: ArrowDownLeftIcon },
    { name: 'ArrowUpLeft',     component: ArrowUpLeftIcon },
    { name: 'ArrowDownRight',  component: ArrowDownRightIcon },
    { name: 'ChevronLeft',     component: ChevronLeftIcon },
    { name: 'ChevronRight',    component: ChevronRightIcon },
    { name: 'ChevronUp',       component: ChevronUpIcon },
    { name: 'ChevronDown',     component: ChevronDownIcon },
    { name: 'ChevronsLeft',    component: ChevronsLeftIcon },
    { name: 'ChevronsRight',   component: ChevronsRightIcon },
    { name: 'ChevronsUp',      component: ChevronsUpIcon },
    { name: 'ChevronsDown',    component: ChevronsDownIcon },
    { name: 'CornerUpLeft',    component: CornerUpLeftIcon },
    { name: 'CornerUpRight',   component: CornerUpRightIcon },
    { name: 'Move',            component: MoveIcon },
    { name: 'ExternalLink',    component: ExternalLinkIcon },
    { name: 'Link',            component: LinkIcon },
    // Favorites
    { name: 'Star',       component: StarIcon },
    { name: 'StarOff',    component: StarOffIcon },
    { name: 'Heart',      component: HeartIcon },
    { name: 'Bookmark',   component: BookmarkIcon },
    // Files
    { name: 'File',        component: FileIcon },
    { name: 'FileText',    component: FileTextIcon },
    { name: 'FilePlus',    component: FilePlusIcon },
    { name: 'FileMinus',   component: FileMinusIcon },
    { name: 'FileCheck',   component: FileCheckIcon },
    { name: 'FileX',       component: FileXIcon },
    { name: 'FileCode',    component: FileCodeIcon },
    { name: 'FileImage',   component: FileImageIcon },
    { name: 'FileVideo',   component: FileVideoIcon },
    { name: 'Folder',      component: FolderIcon },
    { name: 'FolderOpen',  component: FolderOpenIcon },
    { name: 'FolderPlus',  component: FolderPlusIcon },
    { name: 'FolderMinus', component: FolderMinusIcon },
    // Calendar & time
    { name: 'Calendar',    component: CalendarIcon },
    { name: 'Clock',       component: ClockIcon },
    { name: 'Timer',       component: TimerIcon },
    { name: 'AlarmClock',  component: AlarmClockIcon },
    { name: 'Watch',       component: WatchIcon },
    // Location
    { name: 'MapPin',    component: MapPinIcon },
    { name: 'Map',       component: MapIcon },
    { name: 'Navigation',component: NavigationIcon },
    { name: 'Locate',    component: LocateIcon },
    { name: 'Compass',   component: CompassIcon },
    // Media
    { name: 'Play',        component: PlayIcon },
    { name: 'Pause',       component: PauseIcon },
    { name: 'StopCircle',  component: StopCircleIcon },
    { name: 'SkipBack',    component: SkipBackIcon },
    { name: 'SkipForward', component: SkipForwardIcon },
    { name: 'Rewind',      component: RewindIcon },
    { name: 'FastForward', component: FastForwardIcon },
    { name: 'Volume1',     component: Volume1Icon },
    { name: 'Volume2',     component: Volume2Icon },
    { name: 'VolumeX',     component: VolumeXIcon },
    { name: 'Mic',         component: MicIcon },
    { name: 'MicOff',      component: MicOffIcon },
    { name: 'Camera',      component: CameraIcon },
    { name: 'CameraOff',   component: CameraOffIcon },
    { name: 'Radio',       component: RadioIcon },
    { name: 'Tv',          component: TvIcon },
    // Design
    { name: 'Image',          component: ImageIcon },
    { name: 'Video',          component: VideoIcon },
    { name: 'Music',          component: MusicIcon },
    { name: 'Crop',           component: CropIcon },
    { name: 'FlipHorizontal', component: FlipHorizontalIcon },
    { name: 'FlipVertical',   component: FlipVerticalIcon },
    { name: 'PenTool',        component: PenToolIcon },
    { name: 'Pencil',         component: PencilIcon },
    { name: 'Highlighter',    component: HighlighterIcon },
    { name: 'Eraser',         component: EraserIcon },
    { name: 'Ruler',          component: RulerIcon },
    // Typography
    { name: 'Type',           component: TypeIcon },
    { name: 'Bold',           component: BoldIcon },
    { name: 'Italic',         component: ItalicIcon },
    { name: 'Underline',      component: UnderlineIcon },
    { name: 'Strikethrough',  component: StrikethroughIcon },
    { name: 'AlignLeft',      component: AlignLeftIcon },
    { name: 'AlignCenter',    component: AlignCenterIcon },
    { name: 'AlignRight',     component: AlignRightIcon },
    { name: 'AlignJustify',   component: AlignJustifyIcon },
    // Code & tech
    { name: 'Code',           component: CodeIcon },
    { name: 'Terminal',       component: TerminalIcon },
    { name: 'Database',       component: DatabaseIcon },
    { name: 'Server',         component: ServerIcon },
    { name: 'Cpu',            component: CpuIcon },
    { name: 'HardDrive',      component: HardDriveIcon },
    { name: 'GitBranch',      component: GitBranchIcon },
    { name: 'GitCommit',      component: GitCommitIcon },
    { name: 'GitMerge',       component: GitMergeIcon },
    { name: 'GitPullRequest', component: GitPullRequestIcon },
    // Cloud & connectivity
    { name: 'Cloud',           component: CloudIcon },
    { name: 'CloudUpload',     component: CloudUploadIcon },
    { name: 'CloudDownload',   component: CloudDownloadIcon },
    { name: 'Wifi',            component: WifiIcon },
    { name: 'WifiOff',         component: WifiOffIcon },
    { name: 'Bluetooth',       component: BluetoothIcon },
    { name: 'BluetoothOff',    component: BluetoothOffIcon },
    // Security
    { name: 'Lock',         component: LockIcon },
    { name: 'Unlock',       component: UnlockIcon },
    { name: 'Key',          component: KeyIcon },
    { name: 'Shield',       component: ShieldIcon },
    { name: 'ShieldCheck',  component: ShieldCheckIcon },
    { name: 'ShieldOff',    component: ShieldOffIcon },
    { name: 'Eye',          component: EyeIcon },
    { name: 'EyeOff',       component: EyeOffIcon },
    { name: 'Fingerprint',  component: FingerprintIcon },
    // Charts
    { name: 'BarChart3',    component: BarChart3Icon },
    { name: 'BarChart2',    component: BarChart2Icon },
    { name: 'PieChart',     component: PieChartIcon },
    { name: 'LineChart',    component: LineChartIcon },
    { name: 'TrendingUp',   component: TrendingUpIcon },
    { name: 'TrendingDown', component: TrendingDownIcon },
    { name: 'Activity',     component: ActivityIcon },
    // Layout & UI
    { name: 'Layers',          component: LayersIcon },
    { name: 'Grid',            component: GridIcon },
    { name: 'List',            component: ListIcon },
    { name: 'Layout',          component: LayoutIcon },
    { name: 'LayoutDashboard', component: LayoutDashboardIcon },
    { name: 'Sidebar',         component: SidebarIcon },
    { name: 'PanelLeft',       component: PanelLeftIcon },
    { name: 'PanelRight',      component: PanelRightIcon },
    { name: 'Columns',         component: ColumnsIcon },
    { name: 'Table',           component: TableIcon },
    { name: 'Maximize',        component: MaximizeIcon },
    { name: 'Minimize',        component: MinimizeIcon },
    { name: 'MoreHorizontal',  component: MoreHorizontalIcon },
    { name: 'MoreVertical',    component: MoreVerticalIcon },
    { name: 'Filter',          component: FilterIcon },
    { name: 'SortAsc',         component: SortAscIcon },
    { name: 'SortDesc',        component: SortDescIcon },
    { name: 'Slash',           component: SlashIcon },
    { name: 'Hash',            component: HashIcon },
    { name: 'AtSign',          component: AtSignIcon },
    // Commerce
    { name: 'ShoppingCart',  component: ShoppingCartIcon },
    { name: 'ShoppingBag',   component: ShoppingBagIcon },
    { name: 'Package',        component: PackageIcon },
    { name: 'CreditCard',     component: CreditCardIcon },
    { name: 'DollarSign',     component: DollarSignIcon },
    { name: 'Receipt',        component: ReceiptIcon },
    { name: 'Tag',            component: TagIcon },
    { name: 'Ticket',         component: TicketIcon },
    { name: 'Percent',        component: PercentIcon },
    // Business
    { name: 'Building',        component: BuildingIcon },
    { name: 'Building2',       component: Building2Icon },
    { name: 'Briefcase',       component: BriefcaseIcon },
    { name: 'GraduationCap',   component: GraduationCapIcon },
    { name: 'Award',           component: AwardIcon },
    { name: 'Badge',           component: BadgeIcon },
    { name: 'ClipboardList',   component: ClipboardListIcon },
    { name: 'ClipboardCheck',  component: ClipboardCheckIcon },
    { name: 'BookOpen',        component: BookOpenIcon },
    { name: 'Book',            component: BookIcon },
    // Devices
    { name: 'Monitor',    component: MonitorIcon },
    { name: 'Smartphone', component: SmartphoneIcon },
    { name: 'Tablet',     component: TabletIcon },
    { name: 'Printer',    component: PrinterIcon },
    // Weather & nature
    { name: 'Sun',              component: SunIcon },
    { name: 'Moon',             component: MoonIcon },
    { name: 'CloudRain',        component: CloudRainIcon },
    { name: 'CloudSnow',        component: CloudSnowIcon },
    { name: 'CloudLightning',   component: CloudLightningIcon },
    { name: 'Wind',             component: WindIcon },
    { name: 'Droplets',         component: DropletsIcon },
    { name: 'Thermometer',      component: ThermometerIcon },
    { name: 'Snowflake',        component: SnowflakeIcon },
    { name: 'Flame',            component: FlameIcon },
    { name: 'Zap',              component: ZapIcon },
    { name: 'Leaf',             component: LeafIcon },
    // Misc
    { name: 'Sparkles',     component: SparklesIcon },
    { name: 'Wand2',        component: Wand2Icon },
    { name: 'Globe',        component: GlobeIcon },
    { name: 'Crosshair',    component: CrosshairIcon },
    { name: 'Target',       component: TargetIcon },
    { name: 'MousePointer', component: MousePointerIcon },
    { name: 'Hand',         component: HandIcon },
    { name: 'Coffee',       component: CoffeeIcon },
    { name: 'Settings',     component: SettingsIcon },
    { name: 'Wrench',       component: WrenchIcon },
    { name: 'Sliders',      component: SlidersIcon },
    { name: 'Search',       component: SearchIcon },
    { name: 'X',            component: XIcon },
];

interface Tooltip {
    visible: boolean;
    name:    string;
    x:       number;
    y:       number;
}

const query    = ref('');
const iconSize = ref(20);
const copied   = ref('');
const tooltip  = reactive<Tooltip>({ visible: false, name: '', x: 0, y: 0 });

const filtered = computed(() => {
    if (!query.value) return allIcons;
    const q = query.value.toLowerCase();
    return allIcons.filter(i => i.name.toLowerCase().includes(q));
});

function copy(name: string) {
    const code = `import { ${name}Icon } from '@lucide/vue'`;
    navigator.clipboard?.writeText(code).catch(() => {});
    copied.value = name;
    setTimeout(() => { if (copied.value === name) copied.value = ''; }, 1500);
}

function showTooltip(e: MouseEvent, name: string) {
    const el = e.currentTarget as HTMLElement;
    const rect = el.getBoundingClientRect();
    tooltip.name    = name;
    tooltip.x       = rect.left + rect.width / 2;
    tooltip.y       = rect.top - 10;
    tooltip.visible = true;
}

function hideTooltip() {
    tooltip.visible = false;
}
</script>

<style scoped>
/* ── Base (mobile-first) ── */
.page { padding: 12px; display: flex; flex-direction: column; gap: 14px; }
.page__header { display: flex; align-items: center; justify-content: space-between; }
.page__title  { font-size: 18px; font-weight: 800; color: var(--color-text-primary); font-family: var(--font-heading); letter-spacing: -0.02em; }
.page__sub    { font-size: 12px; color: var(--color-text-muted); margin-top: 2px; }

.ig__toolbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ig__search-wrap {
    display: flex; align-items: center; gap: 8px;
    flex: 1; min-width: 0;
    border: 1.5px solid var(--color-border); border-radius: 9px;
    padding: 8px 12px; background: var(--color-surface);
    transition: border-color 150ms ease;
}
.ig__search-wrap:focus-within { border-color: #6366f1; }
.ig__search-icon { color: var(--color-text-subtle); flex-shrink: 0; }
.ig__search {
    flex: 1; border: none; outline: none; background: transparent;
    font-size: 13px; color: var(--color-text-primary); font-family: var(--font-sans);
    min-width: 0;
}
.ig__clear {
    display: flex; align-items: center; border: none; background: transparent;
    cursor: pointer; color: var(--color-text-subtle); padding: 0;
}
.ig__size-toggle { display: none; gap: 4px; }
.ig__size-btn {
    padding: 6px 10px; border-radius: 7px; border: 1.5px solid var(--color-border);
    background: transparent; font-size: 12px; font-weight: 500; cursor: pointer;
    font-family: var(--font-sans); color: var(--color-text-muted); transition: all 120ms ease;
}
.ig__size-btn--active { background: #6366f1; color: #fff; border-color: #6366f1; }

/* Grid: 2 cols on smallest screens */
.ig__grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
}

/* sm ≥ 480px → 3 cols */
@media (min-width: 480px) {
    .page { padding: 16px; gap: 16px; }
    .page__title { font-size: 19px; }
    .page__sub   { font-size: 13px; }
    .ig__size-toggle { display: flex; }
    .ig__grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
}

/* md ≥ 768px → 4 cols, restore full padding */
@media (min-width: 768px) {
    .page { padding: 24px; gap: 20px; }
    .page__title { font-size: 20px; }
    .ig__toolbar { flex-wrap: nowrap; }
    .ig__search-wrap { max-width: 320px; }
    .ig__grid { grid-template-columns: repeat(4, 1fr); }
}

/* lg ≥ 1024px → 5 cols */
@media (min-width: 1024px) {
    .ig__grid { grid-template-columns: repeat(5, 1fr); }
}

/* xl ≥ 1280px → auto-fill fluid */
@media (min-width: 1280px) {
    .ig__grid { grid-template-columns: repeat(auto-fill, minmax(95px, 1fr)); }
}
.ig__item {
    position: relative; display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 10px 6px; border-radius: 10px; border: 1.5px solid var(--color-border);
    background: var(--color-surface); cursor: pointer; transition: all 150ms ease;
    color: var(--color-text-muted);
}
@media (min-width: 480px) {
    .ig__item { padding: 14px 8px; gap: 8px; }
}
.ig__item:hover {
    border-color: #6366f1; color: #6366f1;
    background: color-mix(in srgb, #6366f1 5%, var(--color-surface));
}
.ig__item--copied { border-color: #10b981; color: #10b981; background: rgba(16,185,129,0.05); }
.ig__item-name {
    font-size: 10px; font-family: var(--font-mono); color: var(--color-text-subtle);
    text-align: center; line-height: 1.3; word-break: break-all;
}
.ig__copied-badge {
    position: absolute; top: -8px; right: -8px;
    background: #10b981; color: #fff; font-size: 9px; font-weight: 700;
    padding: 2px 6px; border-radius: 99px; white-space: nowrap;
}
.ig__empty { grid-column: 1 / -1; text-align: center; padding: 48px; font-size: 13px; color: var(--color-text-subtle); }

/* ig__item transition */
.ig-item-enter-active, .ig-item-leave-active { transition: opacity 150ms ease, transform 150ms ease; }
.ig-item-enter-from, .ig-item-leave-to { opacity: 0; transform: scale(0.92); }
</style>

<!-- Tooltip style global (teleported to body, cannot be scoped) -->
<style>
.ig__tooltip {
    position: fixed;
    transform: translate(-50%, -100%);
    z-index: 9999;
    pointer-events: none;
    background: #1e1b4b;
    color: #e0e7ff;
    font-size: 11.5px;
    font-family: 'JetBrains Mono', monospace;
    padding: 6px 11px;
    border-radius: 8px;
    white-space: nowrap;
    box-shadow: 0 4px 16px rgba(0,0,0,0.25);
    display: flex;
    align-items: center;
    gap: 0;
    line-height: 1;
    margin-top: -4px;
}
.ig__tooltip::after {
    content: '';
    position: absolute;
    top: 100%; left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: #1e1b4b;
}
.ig__tooltip-import { color: #818cf8; }
.ig__tooltip-name   { color: #fbbf24; font-weight: 700; margin: 0 1px; }
</style>

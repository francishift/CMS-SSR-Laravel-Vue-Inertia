<script setup>
/**
 * Componente Reutilizable IconPicker (Selector Visual de Iconos PrimeIcons).
 *
 * Ofrece una interfaz visual, categorizada y buscable en tiempo real para seleccionar iconos de PrimeIcons.
 * Compatible con v-model.
 */
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Seleccionar icono...' },
    label: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const modalAbierto = ref(false);
const busqueda = ref('');
const categoriaActiva = ref('todos');
const catalogoIconos = [
    {
        "name": "pi pi-folder-plus",
        "label": "Folder Plus",
        "category": "acciones",
        "keywords": [
            "folder",
            "plus"
        ]
    },
    {
        "name": "pi pi-receipt",
        "label": "Receipt",
        "category": "negocio",
        "keywords": [
            "receipt"
        ]
    },
    {
        "name": "pi pi-asterisk",
        "label": "Asterisk",
        "category": "info",
        "keywords": [
            "asterisk"
        ]
    },
    {
        "name": "pi pi-face-smile",
        "label": "Face Smile",
        "category": "social",
        "keywords": [
            "face",
            "smile"
        ]
    },
    {
        "name": "pi pi-pinterest",
        "label": "Pinterest",
        "category": "social",
        "keywords": [
            "pinterest"
        ]
    },
    {
        "name": "pi pi-expand",
        "label": "Expand",
        "category": "info",
        "keywords": [
            "expand"
        ]
    },
    {
        "name": "pi pi-pen-to-square",
        "label": "Pen To Square",
        "category": "acciones",
        "keywords": [
            "pen",
            "to",
            "square"
        ]
    },
    {
        "name": "pi pi-wave-pulse",
        "label": "Wave Pulse",
        "category": "info",
        "keywords": [
            "wave",
            "pulse"
        ]
    },
    {
        "name": "pi pi-turkish-lira",
        "label": "Turkish Lira",
        "category": "negocio",
        "keywords": [
            "turkish",
            "lira"
        ]
    },
    {
        "name": "pi pi-spinner-dotted",
        "label": "Spinner Dotted",
        "category": "navegacion",
        "keywords": [
            "spinner",
            "dotted"
        ]
    },
    {
        "name": "pi pi-crown",
        "label": "Crown",
        "category": "info",
        "keywords": [
            "crown"
        ]
    },
    {
        "name": "pi pi-pause-circle",
        "label": "Pause Circle",
        "category": "contenido",
        "keywords": [
            "pause",
            "circle"
        ]
    },
    {
        "name": "pi pi-warehouse",
        "label": "Warehouse",
        "category": "info",
        "keywords": [
            "warehouse"
        ]
    },
    {
        "name": "pi pi-objects-column",
        "label": "Objects Column",
        "category": "info",
        "keywords": [
            "objects",
            "column"
        ]
    },
    {
        "name": "pi pi-clipboard",
        "label": "Clipboard",
        "category": "info",
        "keywords": [
            "clipboard"
        ]
    },
    {
        "name": "pi pi-play-circle",
        "label": "Play Circle",
        "category": "contenido",
        "keywords": [
            "play",
            "circle"
        ]
    },
    {
        "name": "pi pi-venus",
        "label": "Venus",
        "category": "info",
        "keywords": [
            "venus"
        ]
    },
    {
        "name": "pi pi-cart-minus",
        "label": "Cart Minus",
        "category": "acciones",
        "keywords": [
            "cart",
            "minus"
        ]
    },
    {
        "name": "pi pi-file-plus",
        "label": "File Plus",
        "category": "acciones",
        "keywords": [
            "file",
            "plus"
        ]
    },
    {
        "name": "pi pi-microchip",
        "label": "Microchip",
        "category": "info",
        "keywords": [
            "microchip"
        ]
    },
    {
        "name": "pi pi-twitch",
        "label": "Twitch",
        "category": "social",
        "keywords": [
            "twitch"
        ]
    },
    {
        "name": "pi pi-building-columns",
        "label": "Building Columns",
        "category": "negocio",
        "keywords": [
            "building",
            "columns"
        ]
    },
    {
        "name": "pi pi-file-check",
        "label": "File Check",
        "category": "acciones",
        "keywords": [
            "file",
            "check"
        ]
    },
    {
        "name": "pi pi-microchip-ai",
        "label": "Microchip Ai",
        "category": "info",
        "keywords": [
            "microchip",
            "ai"
        ]
    },
    {
        "name": "pi pi-trophy",
        "label": "Trophy",
        "category": "info",
        "keywords": [
            "trophy"
        ]
    },
    {
        "name": "pi pi-barcode",
        "label": "Barcode",
        "category": "contenido",
        "keywords": [
            "barcode"
        ]
    },
    {
        "name": "pi pi-file-arrow-up",
        "label": "File Arrow Up",
        "category": "navegacion",
        "keywords": [
            "file",
            "arrow",
            "up"
        ]
    },
    {
        "name": "pi pi-mars",
        "label": "Mars",
        "category": "info",
        "keywords": [
            "mars"
        ]
    },
    {
        "name": "pi pi-tiktok",
        "label": "Tiktok",
        "category": "social",
        "keywords": [
            "tiktok"
        ]
    },
    {
        "name": "pi pi-arrow-up-right-and-arrow-down-left-from-center",
        "label": "Arrow Up Right And Arrow Down Left From Center",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "up",
            "right",
            "and",
            "arrow",
            "down",
            "left",
            "from",
            "center"
        ]
    },
    {
        "name": "pi pi-ethereum",
        "label": "Ethereum",
        "category": "acciones",
        "keywords": [
            "ethereum"
        ]
    },
    {
        "name": "pi pi-list-check",
        "label": "List Check",
        "category": "acciones",
        "keywords": [
            "list",
            "check"
        ]
    },
    {
        "name": "pi pi-thumbtack",
        "label": "Thumbtack",
        "category": "acciones",
        "keywords": [
            "thumbtack"
        ]
    },
    {
        "name": "pi pi-arrow-down-left-and-arrow-up-right-to-center",
        "label": "Arrow Down Left And Arrow Up Right To Center",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "down",
            "left",
            "and",
            "arrow",
            "up",
            "right",
            "to",
            "center"
        ]
    },
    {
        "name": "pi pi-equals",
        "label": "Equals",
        "category": "info",
        "keywords": [
            "equals"
        ]
    },
    {
        "name": "pi pi-lightbulb",
        "label": "Lightbulb",
        "category": "info",
        "keywords": [
            "lightbulb"
        ]
    },
    {
        "name": "pi pi-star-half",
        "label": "Star Half",
        "category": "social",
        "keywords": [
            "star",
            "half"
        ]
    },
    {
        "name": "pi pi-address-book",
        "label": "Address Book",
        "category": "contenido",
        "keywords": [
            "address",
            "book"
        ]
    },
    {
        "name": "pi pi-chart-scatter",
        "label": "Chart Scatter",
        "category": "social",
        "keywords": [
            "chart",
            "scatter"
        ]
    },
    {
        "name": "pi pi-indian-rupee",
        "label": "Indian Rupee",
        "category": "negocio",
        "keywords": [
            "indian",
            "rupee"
        ]
    },
    {
        "name": "pi pi-star-half-fill",
        "label": "Star Half Fill",
        "category": "social",
        "keywords": [
            "star",
            "half",
            "fill"
        ]
    },
    {
        "name": "pi pi-cart-arrow-down",
        "label": "Cart Arrow Down",
        "category": "navegacion",
        "keywords": [
            "cart",
            "arrow",
            "down"
        ]
    },
    {
        "name": "pi pi-calendar-clock",
        "label": "Calendar Clock",
        "category": "acciones",
        "keywords": [
            "calendar",
            "clock"
        ]
    },
    {
        "name": "pi pi-sort-up-fill",
        "label": "Sort Up Fill",
        "category": "acciones",
        "keywords": [
            "sort",
            "up",
            "fill"
        ]
    },
    {
        "name": "pi pi-sparkles",
        "label": "Sparkles",
        "category": "info",
        "keywords": [
            "sparkles"
        ]
    },
    {
        "name": "pi pi-bullseye",
        "label": "Bullseye",
        "category": "acciones",
        "keywords": [
            "bullseye"
        ]
    },
    {
        "name": "pi pi-sort-down-fill",
        "label": "Sort Down Fill",
        "category": "acciones",
        "keywords": [
            "sort",
            "down",
            "fill"
        ]
    },
    {
        "name": "pi pi-graduation-cap",
        "label": "Graduation Cap",
        "category": "social",
        "keywords": [
            "graduation",
            "cap"
        ]
    },
    {
        "name": "pi pi-hammer",
        "label": "Hammer",
        "category": "info",
        "keywords": [
            "hammer"
        ]
    },
    {
        "name": "pi pi-bell-slash",
        "label": "Bell Slash",
        "category": "social",
        "keywords": [
            "bell",
            "slash"
        ]
    },
    {
        "name": "pi pi-gauge",
        "label": "Gauge",
        "category": "info",
        "keywords": [
            "gauge"
        ]
    },
    {
        "name": "pi pi-shop",
        "label": "Shop",
        "category": "negocio",
        "keywords": [
            "shop"
        ]
    },
    {
        "name": "pi pi-headphones",
        "label": "Headphones",
        "category": "social",
        "keywords": [
            "headphones"
        ]
    },
    {
        "name": "pi pi-eraser",
        "label": "Eraser",
        "category": "acciones",
        "keywords": [
            "eraser"
        ]
    },
    {
        "name": "pi pi-stopwatch",
        "label": "Stopwatch",
        "category": "contenido",
        "keywords": [
            "stopwatch"
        ]
    },
    {
        "name": "pi pi-verified",
        "label": "Verified",
        "category": "info",
        "keywords": [
            "verified"
        ]
    },
    {
        "name": "pi pi-delete-left",
        "label": "Delete Left",
        "category": "info",
        "keywords": [
            "delete",
            "left"
        ]
    },
    {
        "name": "pi pi-hourglass",
        "label": "Hourglass",
        "category": "info",
        "keywords": [
            "hourglass"
        ]
    },
    {
        "name": "pi pi-truck",
        "label": "Truck",
        "category": "info",
        "keywords": [
            "truck"
        ]
    },
    {
        "name": "pi pi-wrench",
        "label": "Wrench",
        "category": "acciones",
        "keywords": [
            "wrench"
        ]
    },
    {
        "name": "pi pi-microphone",
        "label": "Microphone",
        "category": "acciones",
        "keywords": [
            "microphone"
        ]
    },
    {
        "name": "pi pi-megaphone",
        "label": "Megaphone",
        "category": "social",
        "keywords": [
            "megaphone"
        ]
    },
    {
        "name": "pi pi-arrow-right-arrow-left",
        "label": "Arrow Right Arrow Left",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "right",
            "arrow",
            "left"
        ]
    },
    {
        "name": "pi pi-bitcoin",
        "label": "Bitcoin",
        "category": "negocio",
        "keywords": [
            "bitcoin"
        ]
    },
    {
        "name": "pi pi-file-edit",
        "label": "File Edit",
        "category": "contenido",
        "keywords": [
            "file",
            "edit"
        ]
    },
    {
        "name": "pi pi-language",
        "label": "Language",
        "category": "info",
        "keywords": [
            "language"
        ]
    },
    {
        "name": "pi pi-file-export",
        "label": "File Export",
        "category": "contenido",
        "keywords": [
            "file",
            "export"
        ]
    },
    {
        "name": "pi pi-file-import",
        "label": "File Import",
        "category": "contenido",
        "keywords": [
            "file",
            "import"
        ]
    },
    {
        "name": "pi pi-file-word",
        "label": "File Word",
        "category": "contenido",
        "keywords": [
            "file",
            "word"
        ]
    },
    {
        "name": "pi pi-gift",
        "label": "Gift",
        "category": "info",
        "keywords": [
            "gift"
        ]
    },
    {
        "name": "pi pi-cart-plus",
        "label": "Cart Plus",
        "category": "acciones",
        "keywords": [
            "cart",
            "plus"
        ]
    },
    {
        "name": "pi pi-thumbs-down-fill",
        "label": "Thumbs Down Fill",
        "category": "acciones",
        "keywords": [
            "thumbs",
            "down",
            "fill"
        ]
    },
    {
        "name": "pi pi-thumbs-up-fill",
        "label": "Thumbs Up Fill",
        "category": "acciones",
        "keywords": [
            "thumbs",
            "up",
            "fill"
        ]
    },
    {
        "name": "pi pi-arrows-alt",
        "label": "Arrows Alt",
        "category": "navegacion",
        "keywords": [
            "arrows",
            "alt"
        ]
    },
    {
        "name": "pi pi-calculator",
        "label": "Calculator",
        "category": "social",
        "keywords": [
            "calculator"
        ]
    },
    {
        "name": "pi pi-sort-alt-slash",
        "label": "Sort Alt Slash",
        "category": "acciones",
        "keywords": [
            "sort",
            "alt",
            "slash"
        ]
    },
    {
        "name": "pi pi-arrows-h",
        "label": "Arrows H",
        "category": "navegacion",
        "keywords": [
            "arrows",
            "h"
        ]
    },
    {
        "name": "pi pi-arrows-v",
        "label": "Arrows V",
        "category": "navegacion",
        "keywords": [
            "arrows",
            "v"
        ]
    },
    {
        "name": "pi pi-pound",
        "label": "Pound",
        "category": "negocio",
        "keywords": [
            "pound"
        ]
    },
    {
        "name": "pi pi-prime",
        "label": "Prime",
        "category": "info",
        "keywords": [
            "prime"
        ]
    },
    {
        "name": "pi pi-chart-pie",
        "label": "Chart Pie",
        "category": "negocio",
        "keywords": [
            "chart",
            "pie"
        ]
    },
    {
        "name": "pi pi-reddit",
        "label": "Reddit",
        "category": "social",
        "keywords": [
            "reddit"
        ]
    },
    {
        "name": "pi pi-code",
        "label": "Code",
        "category": "contenido",
        "keywords": [
            "code"
        ]
    },
    {
        "name": "pi pi-sync",
        "label": "Sync",
        "category": "acciones",
        "keywords": [
            "sync"
        ]
    },
    {
        "name": "pi pi-shopping-bag",
        "label": "Shopping Bag",
        "category": "negocio",
        "keywords": [
            "shopping",
            "bag"
        ]
    },
    {
        "name": "pi pi-server",
        "label": "Server",
        "category": "contenido",
        "keywords": [
            "server"
        ]
    },
    {
        "name": "pi pi-database",
        "label": "Database",
        "category": "contenido",
        "keywords": [
            "database"
        ]
    },
    {
        "name": "pi pi-hashtag",
        "label": "Hashtag",
        "category": "negocio",
        "keywords": [
            "hashtag"
        ]
    },
    {
        "name": "pi pi-bookmark-fill",
        "label": "Bookmark Fill",
        "category": "contenido",
        "keywords": [
            "bookmark",
            "fill"
        ]
    },
    {
        "name": "pi pi-filter-fill",
        "label": "Filter Fill",
        "category": "acciones",
        "keywords": [
            "filter",
            "fill"
        ]
    },
    {
        "name": "pi pi-heart-fill",
        "label": "Heart Fill",
        "category": "social",
        "keywords": [
            "heart",
            "fill"
        ]
    },
    {
        "name": "pi pi-flag-fill",
        "label": "Flag Fill",
        "category": "info",
        "keywords": [
            "flag",
            "fill"
        ]
    },
    {
        "name": "pi pi-circle",
        "label": "Circle",
        "category": "info",
        "keywords": [
            "circle"
        ]
    },
    {
        "name": "pi pi-circle-fill",
        "label": "Circle Fill",
        "category": "info",
        "keywords": [
            "circle",
            "fill"
        ]
    },
    {
        "name": "pi pi-bolt",
        "label": "Bolt",
        "category": "info",
        "keywords": [
            "bolt"
        ]
    },
    {
        "name": "pi pi-history",
        "label": "History",
        "category": "navegacion",
        "keywords": [
            "history"
        ]
    },
    {
        "name": "pi pi-box",
        "label": "Box",
        "category": "negocio",
        "keywords": [
            "box"
        ]
    },
    {
        "name": "pi pi-at",
        "label": "At",
        "category": "social",
        "keywords": [
            "at"
        ]
    },
    {
        "name": "pi pi-arrow-up-right",
        "label": "Arrow Up Right",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "up",
            "right"
        ]
    },
    {
        "name": "pi pi-arrow-up-left",
        "label": "Arrow Up Left",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "up",
            "left"
        ]
    },
    {
        "name": "pi pi-arrow-down-left",
        "label": "Arrow Down Left",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "down",
            "left"
        ]
    },
    {
        "name": "pi pi-arrow-down-right",
        "label": "Arrow Down Right",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "down",
            "right"
        ]
    },
    {
        "name": "pi pi-telegram",
        "label": "Telegram",
        "category": "social",
        "keywords": [
            "telegram"
        ]
    },
    {
        "name": "pi pi-stop-circle",
        "label": "Stop Circle",
        "category": "contenido",
        "keywords": [
            "stop",
            "circle"
        ]
    },
    {
        "name": "pi pi-stop",
        "label": "Stop",
        "category": "contenido",
        "keywords": [
            "stop"
        ]
    },
    {
        "name": "pi pi-whatsapp",
        "label": "Whatsapp",
        "category": "social",
        "keywords": [
            "whatsapp"
        ]
    },
    {
        "name": "pi pi-building",
        "label": "Building",
        "category": "negocio",
        "keywords": [
            "building"
        ]
    },
    {
        "name": "pi pi-qrcode",
        "label": "Qrcode",
        "category": "contenido",
        "keywords": [
            "qrcode"
        ]
    },
    {
        "name": "pi pi-car",
        "label": "Car",
        "category": "info",
        "keywords": [
            "car"
        ]
    },
    {
        "name": "pi pi-instagram",
        "label": "Instagram",
        "category": "social",
        "keywords": [
            "instagram"
        ]
    },
    {
        "name": "pi pi-linkedin",
        "label": "Linkedin",
        "category": "navegacion",
        "keywords": [
            "linkedin"
        ]
    },
    {
        "name": "pi pi-send",
        "label": "Send",
        "category": "acciones",
        "keywords": [
            "send"
        ]
    },
    {
        "name": "pi pi-slack",
        "label": "Slack",
        "category": "social",
        "keywords": [
            "slack"
        ]
    },
    {
        "name": "pi pi-sun",
        "label": "Sun",
        "category": "info",
        "keywords": [
            "sun"
        ]
    },
    {
        "name": "pi pi-moon",
        "label": "Moon",
        "category": "info",
        "keywords": [
            "moon"
        ]
    },
    {
        "name": "pi pi-vimeo",
        "label": "Vimeo",
        "category": "social",
        "keywords": [
            "vimeo"
        ]
    },
    {
        "name": "pi pi-youtube",
        "label": "Youtube",
        "category": "social",
        "keywords": [
            "youtube"
        ]
    },
    {
        "name": "pi pi-flag",
        "label": "Flag",
        "category": "info",
        "keywords": [
            "flag"
        ]
    },
    {
        "name": "pi pi-wallet",
        "label": "Wallet",
        "category": "negocio",
        "keywords": [
            "wallet"
        ]
    },
    {
        "name": "pi pi-map",
        "label": "Map",
        "category": "navegacion",
        "keywords": [
            "map"
        ]
    },
    {
        "name": "pi pi-link",
        "label": "Link",
        "category": "navegacion",
        "keywords": [
            "link"
        ]
    },
    {
        "name": "pi pi-credit-card",
        "label": "Credit Card",
        "category": "negocio",
        "keywords": [
            "credit",
            "card"
        ]
    },
    {
        "name": "pi pi-discord",
        "label": "Discord",
        "category": "social",
        "keywords": [
            "discord"
        ]
    },
    {
        "name": "pi pi-percentage",
        "label": "Percentage",
        "category": "negocio",
        "keywords": [
            "percentage"
        ]
    },
    {
        "name": "pi pi-euro",
        "label": "Euro",
        "category": "negocio",
        "keywords": [
            "euro"
        ]
    },
    {
        "name": "pi pi-book",
        "label": "Book",
        "category": "contenido",
        "keywords": [
            "book"
        ]
    },
    {
        "name": "pi pi-shield",
        "label": "Shield",
        "category": "info",
        "keywords": [
            "shield"
        ]
    },
    {
        "name": "pi pi-paypal",
        "label": "Paypal",
        "category": "info",
        "keywords": [
            "paypal"
        ]
    },
    {
        "name": "pi pi-amazon",
        "label": "Amazon",
        "category": "info",
        "keywords": [
            "amazon"
        ]
    },
    {
        "name": "pi pi-phone",
        "label": "Phone",
        "category": "social",
        "keywords": [
            "phone"
        ]
    },
    {
        "name": "pi pi-filter-slash",
        "label": "Filter Slash",
        "category": "acciones",
        "keywords": [
            "filter",
            "slash"
        ]
    },
    {
        "name": "pi pi-facebook",
        "label": "Facebook",
        "category": "contenido",
        "keywords": [
            "facebook"
        ]
    },
    {
        "name": "pi pi-github",
        "label": "Github",
        "category": "acciones",
        "keywords": [
            "github"
        ]
    },
    {
        "name": "pi pi-twitter",
        "label": "Twitter",
        "category": "social",
        "keywords": [
            "twitter"
        ]
    },
    {
        "name": "pi pi-step-backward-alt",
        "label": "Step Backward Alt",
        "category": "navegacion",
        "keywords": [
            "step",
            "backward",
            "alt"
        ]
    },
    {
        "name": "pi pi-step-forward-alt",
        "label": "Step Forward Alt",
        "category": "navegacion",
        "keywords": [
            "step",
            "forward",
            "alt"
        ]
    },
    {
        "name": "pi pi-forward",
        "label": "Forward",
        "category": "navegacion",
        "keywords": [
            "forward"
        ]
    },
    {
        "name": "pi pi-backward",
        "label": "Backward",
        "category": "navegacion",
        "keywords": [
            "backward"
        ]
    },
    {
        "name": "pi pi-fast-backward",
        "label": "Fast Backward",
        "category": "navegacion",
        "keywords": [
            "fast",
            "backward"
        ]
    },
    {
        "name": "pi pi-fast-forward",
        "label": "Fast Forward",
        "category": "navegacion",
        "keywords": [
            "fast",
            "forward"
        ]
    },
    {
        "name": "pi pi-pause",
        "label": "Pause",
        "category": "contenido",
        "keywords": [
            "pause"
        ]
    },
    {
        "name": "pi pi-play",
        "label": "Play",
        "category": "contenido",
        "keywords": [
            "play"
        ]
    },
    {
        "name": "pi pi-compass",
        "label": "Compass",
        "category": "navegacion",
        "keywords": [
            "compass"
        ]
    },
    {
        "name": "pi pi-id-card",
        "label": "Id Card",
        "category": "social",
        "keywords": [
            "id",
            "card"
        ]
    },
    {
        "name": "pi pi-ticket",
        "label": "Ticket",
        "category": "negocio",
        "keywords": [
            "ticket"
        ]
    },
    {
        "name": "pi pi-file-o",
        "label": "File O",
        "category": "contenido",
        "keywords": [
            "file",
            "o"
        ]
    },
    {
        "name": "pi pi-reply",
        "label": "Reply",
        "category": "info",
        "keywords": [
            "reply"
        ]
    },
    {
        "name": "pi pi-directions-alt",
        "label": "Directions Alt",
        "category": "info",
        "keywords": [
            "directions",
            "alt"
        ]
    },
    {
        "name": "pi pi-directions",
        "label": "Directions",
        "category": "info",
        "keywords": [
            "directions"
        ]
    },
    {
        "name": "pi pi-thumbs-up",
        "label": "Thumbs Up",
        "category": "acciones",
        "keywords": [
            "thumbs",
            "up"
        ]
    },
    {
        "name": "pi pi-thumbs-down",
        "label": "Thumbs Down",
        "category": "acciones",
        "keywords": [
            "thumbs",
            "down"
        ]
    },
    {
        "name": "pi pi-sort-numeric-down-alt",
        "label": "Sort Numeric Down Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "numeric",
            "down",
            "alt"
        ]
    },
    {
        "name": "pi pi-sort-numeric-up-alt",
        "label": "Sort Numeric Up Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "numeric",
            "up",
            "alt"
        ]
    },
    {
        "name": "pi pi-sort-alpha-down-alt",
        "label": "Sort Alpha Down Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "alpha",
            "down",
            "alt"
        ]
    },
    {
        "name": "pi pi-sort-alpha-up-alt",
        "label": "Sort Alpha Up Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "alpha",
            "up",
            "alt"
        ]
    },
    {
        "name": "pi pi-sort-numeric-down",
        "label": "Sort Numeric Down",
        "category": "acciones",
        "keywords": [
            "sort",
            "numeric",
            "down"
        ]
    },
    {
        "name": "pi pi-sort-numeric-up",
        "label": "Sort Numeric Up",
        "category": "acciones",
        "keywords": [
            "sort",
            "numeric",
            "up"
        ]
    },
    {
        "name": "pi pi-sort-alpha-down",
        "label": "Sort Alpha Down",
        "category": "acciones",
        "keywords": [
            "sort",
            "alpha",
            "down"
        ]
    },
    {
        "name": "pi pi-sort-alpha-up",
        "label": "Sort Alpha Up",
        "category": "acciones",
        "keywords": [
            "sort",
            "alpha",
            "up"
        ]
    },
    {
        "name": "pi pi-sort-alt",
        "label": "Sort Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "alt"
        ]
    },
    {
        "name": "pi pi-sort-amount-up",
        "label": "Sort Amount Up",
        "category": "acciones",
        "keywords": [
            "sort",
            "amount",
            "up"
        ]
    },
    {
        "name": "pi pi-sort-amount-down",
        "label": "Sort Amount Down",
        "category": "acciones",
        "keywords": [
            "sort",
            "amount",
            "down"
        ]
    },
    {
        "name": "pi pi-sort-amount-down-alt",
        "label": "Sort Amount Down Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "amount",
            "down",
            "alt"
        ]
    },
    {
        "name": "pi pi-sort-amount-up-alt",
        "label": "Sort Amount Up Alt",
        "category": "acciones",
        "keywords": [
            "sort",
            "amount",
            "up",
            "alt"
        ]
    },
    {
        "name": "pi pi-palette",
        "label": "Palette",
        "category": "acciones",
        "keywords": [
            "palette"
        ]
    },
    {
        "name": "pi pi-undo",
        "label": "Undo",
        "category": "info",
        "keywords": [
            "undo"
        ]
    },
    {
        "name": "pi pi-desktop",
        "label": "Desktop",
        "category": "contenido",
        "keywords": [
            "desktop"
        ]
    },
    {
        "name": "pi pi-sliders-v",
        "label": "Sliders V",
        "category": "acciones",
        "keywords": [
            "sliders",
            "v"
        ]
    },
    {
        "name": "pi pi-sliders-h",
        "label": "Sliders H",
        "category": "acciones",
        "keywords": [
            "sliders",
            "h"
        ]
    },
    {
        "name": "pi pi-search-plus",
        "label": "Search Plus",
        "category": "acciones",
        "keywords": [
            "search",
            "plus"
        ]
    },
    {
        "name": "pi pi-search-minus",
        "label": "Search Minus",
        "category": "acciones",
        "keywords": [
            "search",
            "minus"
        ]
    },
    {
        "name": "pi pi-file-excel",
        "label": "File Excel",
        "category": "contenido",
        "keywords": [
            "file",
            "excel"
        ]
    },
    {
        "name": "pi pi-file-pdf",
        "label": "File Pdf",
        "category": "contenido",
        "keywords": [
            "file",
            "pdf"
        ]
    },
    {
        "name": "pi pi-check-square",
        "label": "Check Square",
        "category": "acciones",
        "keywords": [
            "check",
            "square"
        ]
    },
    {
        "name": "pi pi-chart-line",
        "label": "Chart Line",
        "category": "negocio",
        "keywords": [
            "chart",
            "line"
        ]
    },
    {
        "name": "pi pi-user-edit",
        "label": "User Edit",
        "category": "social",
        "keywords": [
            "user",
            "edit"
        ]
    },
    {
        "name": "pi pi-exclamation-circle",
        "label": "Exclamation Circle",
        "category": "social",
        "keywords": [
            "exclamation",
            "circle"
        ]
    },
    {
        "name": "pi pi-android",
        "label": "Android",
        "category": "social",
        "keywords": [
            "android"
        ]
    },
    {
        "name": "pi pi-google",
        "label": "Google",
        "category": "social",
        "keywords": [
            "google"
        ]
    },
    {
        "name": "pi pi-apple",
        "label": "Apple",
        "category": "social",
        "keywords": [
            "apple"
        ]
    },
    {
        "name": "pi pi-microsoft",
        "label": "Microsoft",
        "category": "social",
        "keywords": [
            "microsoft"
        ]
    },
    {
        "name": "pi pi-heart",
        "label": "Heart",
        "category": "social",
        "keywords": [
            "heart"
        ]
    },
    {
        "name": "pi pi-mobile",
        "label": "Mobile",
        "category": "contenido",
        "keywords": [
            "mobile"
        ]
    },
    {
        "name": "pi pi-tablet",
        "label": "Tablet",
        "category": "acciones",
        "keywords": [
            "tablet"
        ]
    },
    {
        "name": "pi pi-key",
        "label": "Key",
        "category": "acciones",
        "keywords": [
            "key"
        ]
    },
    {
        "name": "pi pi-shopping-cart",
        "label": "Shopping Cart",
        "category": "negocio",
        "keywords": [
            "shopping",
            "cart"
        ]
    },
    {
        "name": "pi pi-comments",
        "label": "Comments",
        "category": "social",
        "keywords": [
            "comments"
        ]
    },
    {
        "name": "pi pi-comment",
        "label": "Comment",
        "category": "social",
        "keywords": [
            "comment"
        ]
    },
    {
        "name": "pi pi-briefcase",
        "label": "Briefcase",
        "category": "negocio",
        "keywords": [
            "briefcase"
        ]
    },
    {
        "name": "pi pi-bell",
        "label": "Bell",
        "category": "social",
        "keywords": [
            "bell"
        ]
    },
    {
        "name": "pi pi-paperclip",
        "label": "Paperclip",
        "category": "contenido",
        "keywords": [
            "paperclip"
        ]
    },
    {
        "name": "pi pi-share-alt",
        "label": "Share Alt",
        "category": "acciones",
        "keywords": [
            "share",
            "alt"
        ]
    },
    {
        "name": "pi pi-envelope",
        "label": "Envelope",
        "category": "social",
        "keywords": [
            "envelope"
        ]
    },
    {
        "name": "pi pi-volume-down",
        "label": "Volume Down",
        "category": "contenido",
        "keywords": [
            "volume",
            "down"
        ]
    },
    {
        "name": "pi pi-volume-up",
        "label": "Volume Up",
        "category": "contenido",
        "keywords": [
            "volume",
            "up"
        ]
    },
    {
        "name": "pi pi-volume-off",
        "label": "Volume Off",
        "category": "contenido",
        "keywords": [
            "volume",
            "off"
        ]
    },
    {
        "name": "pi pi-eject",
        "label": "Eject",
        "category": "info",
        "keywords": [
            "eject"
        ]
    },
    {
        "name": "pi pi-money-bill",
        "label": "Money Bill",
        "category": "negocio",
        "keywords": [
            "money",
            "bill"
        ]
    },
    {
        "name": "pi pi-images",
        "label": "Images",
        "category": "contenido",
        "keywords": [
            "images"
        ]
    },
    {
        "name": "pi pi-image",
        "label": "Image",
        "category": "contenido",
        "keywords": [
            "image"
        ]
    },
    {
        "name": "pi pi-sign-in",
        "label": "Sign In",
        "category": "navegacion",
        "keywords": [
            "sign",
            "in"
        ]
    },
    {
        "name": "pi pi-sign-out",
        "label": "Sign Out",
        "category": "navegacion",
        "keywords": [
            "sign",
            "out"
        ]
    },
    {
        "name": "pi pi-wifi",
        "label": "Wifi",
        "category": "info",
        "keywords": [
            "wifi"
        ]
    },
    {
        "name": "pi pi-sitemap",
        "label": "Sitemap",
        "category": "navegacion",
        "keywords": [
            "sitemap"
        ]
    },
    {
        "name": "pi pi-chart-bar",
        "label": "Chart Bar",
        "category": "negocio",
        "keywords": [
            "chart",
            "bar"
        ]
    },
    {
        "name": "pi pi-camera",
        "label": "Camera",
        "category": "contenido",
        "keywords": [
            "camera"
        ]
    },
    {
        "name": "pi pi-dollar",
        "label": "Dollar",
        "category": "negocio",
        "keywords": [
            "dollar"
        ]
    },
    {
        "name": "pi pi-lock-open",
        "label": "Lock Open",
        "category": "acciones",
        "keywords": [
            "lock",
            "open"
        ]
    },
    {
        "name": "pi pi-table",
        "label": "Table",
        "category": "acciones",
        "keywords": [
            "table"
        ]
    },
    {
        "name": "pi pi-map-marker",
        "label": "Map Marker",
        "category": "navegacion",
        "keywords": [
            "map",
            "marker"
        ]
    },
    {
        "name": "pi pi-list",
        "label": "List",
        "category": "acciones",
        "keywords": [
            "list"
        ]
    },
    {
        "name": "pi pi-eye-slash",
        "label": "Eye Slash",
        "category": "acciones",
        "keywords": [
            "eye",
            "slash"
        ]
    },
    {
        "name": "pi pi-eye",
        "label": "Eye",
        "category": "acciones",
        "keywords": [
            "eye"
        ]
    },
    {
        "name": "pi pi-folder-open",
        "label": "Folder Open",
        "category": "acciones",
        "keywords": [
            "folder",
            "open"
        ]
    },
    {
        "name": "pi pi-folder",
        "label": "Folder",
        "category": "contenido",
        "keywords": [
            "folder"
        ]
    },
    {
        "name": "pi pi-video",
        "label": "Video",
        "category": "contenido",
        "keywords": [
            "video"
        ]
    },
    {
        "name": "pi pi-inbox",
        "label": "Inbox",
        "category": "contenido",
        "keywords": [
            "inbox"
        ]
    },
    {
        "name": "pi pi-lock",
        "label": "Lock",
        "category": "acciones",
        "keywords": [
            "lock"
        ]
    },
    {
        "name": "pi pi-unlock",
        "label": "Unlock",
        "category": "acciones",
        "keywords": [
            "unlock"
        ]
    },
    {
        "name": "pi pi-tags",
        "label": "Tags",
        "category": "negocio",
        "keywords": [
            "tags"
        ]
    },
    {
        "name": "pi pi-tag",
        "label": "Tag",
        "category": "negocio",
        "keywords": [
            "tag"
        ]
    },
    {
        "name": "pi pi-power-off",
        "label": "Power Off",
        "category": "info",
        "keywords": [
            "power",
            "off"
        ]
    },
    {
        "name": "pi pi-save",
        "label": "Save",
        "category": "acciones",
        "keywords": [
            "save"
        ]
    },
    {
        "name": "pi pi-question-circle",
        "label": "Question Circle",
        "category": "info",
        "keywords": [
            "question",
            "circle"
        ]
    },
    {
        "name": "pi pi-question",
        "label": "Question",
        "category": "info",
        "keywords": [
            "question"
        ]
    },
    {
        "name": "pi pi-copy",
        "label": "Copy",
        "category": "acciones",
        "keywords": [
            "copy"
        ]
    },
    {
        "name": "pi pi-file",
        "label": "File",
        "category": "contenido",
        "keywords": [
            "file"
        ]
    },
    {
        "name": "pi pi-clone",
        "label": "Clone",
        "category": "info",
        "keywords": [
            "clone"
        ]
    },
    {
        "name": "pi pi-calendar-times",
        "label": "Calendar Times",
        "category": "acciones",
        "keywords": [
            "calendar",
            "times"
        ]
    },
    {
        "name": "pi pi-calendar-minus",
        "label": "Calendar Minus",
        "category": "acciones",
        "keywords": [
            "calendar",
            "minus"
        ]
    },
    {
        "name": "pi pi-calendar-plus",
        "label": "Calendar Plus",
        "category": "acciones",
        "keywords": [
            "calendar",
            "plus"
        ]
    },
    {
        "name": "pi pi-ellipsis-v",
        "label": "Ellipsis V",
        "category": "info",
        "keywords": [
            "ellipsis",
            "v"
        ]
    },
    {
        "name": "pi pi-ellipsis-h",
        "label": "Ellipsis H",
        "category": "info",
        "keywords": [
            "ellipsis",
            "h"
        ]
    },
    {
        "name": "pi pi-bookmark",
        "label": "Bookmark",
        "category": "contenido",
        "keywords": [
            "bookmark"
        ]
    },
    {
        "name": "pi pi-globe",
        "label": "Globe",
        "category": "navegacion",
        "keywords": [
            "globe"
        ]
    },
    {
        "name": "pi pi-replay",
        "label": "Replay",
        "category": "navegacion",
        "keywords": [
            "replay"
        ]
    },
    {
        "name": "pi pi-filter",
        "label": "Filter",
        "category": "acciones",
        "keywords": [
            "filter"
        ]
    },
    {
        "name": "pi pi-print",
        "label": "Print",
        "category": "acciones",
        "keywords": [
            "print"
        ]
    },
    {
        "name": "pi pi-align-right",
        "label": "Align Right",
        "category": "navegacion",
        "keywords": [
            "align",
            "right"
        ]
    },
    {
        "name": "pi pi-align-left",
        "label": "Align Left",
        "category": "navegacion",
        "keywords": [
            "align",
            "left"
        ]
    },
    {
        "name": "pi pi-align-center",
        "label": "Align Center",
        "category": "navegacion",
        "keywords": [
            "align",
            "center"
        ]
    },
    {
        "name": "pi pi-align-justify",
        "label": "Align Justify",
        "category": "navegacion",
        "keywords": [
            "align",
            "justify"
        ]
    },
    {
        "name": "pi pi-cog",
        "label": "Cog",
        "category": "acciones",
        "keywords": [
            "cog"
        ]
    },
    {
        "name": "pi pi-cloud-download",
        "label": "Cloud Download",
        "category": "acciones",
        "keywords": [
            "cloud",
            "download"
        ]
    },
    {
        "name": "pi pi-cloud-upload",
        "label": "Cloud Upload",
        "category": "acciones",
        "keywords": [
            "cloud",
            "upload"
        ]
    },
    {
        "name": "pi pi-cloud",
        "label": "Cloud",
        "category": "info",
        "keywords": [
            "cloud"
        ]
    },
    {
        "name": "pi pi-pencil",
        "label": "Pencil",
        "category": "acciones",
        "keywords": [
            "pencil"
        ]
    },
    {
        "name": "pi pi-users",
        "label": "Users",
        "category": "social",
        "keywords": [
            "users"
        ]
    },
    {
        "name": "pi pi-clock",
        "label": "Clock",
        "category": "acciones",
        "keywords": [
            "clock"
        ]
    },
    {
        "name": "pi pi-user-minus",
        "label": "User Minus",
        "category": "acciones",
        "keywords": [
            "user",
            "minus"
        ]
    },
    {
        "name": "pi pi-user-plus",
        "label": "User Plus",
        "category": "acciones",
        "keywords": [
            "user",
            "plus"
        ]
    },
    {
        "name": "pi pi-trash",
        "label": "Trash",
        "category": "acciones",
        "keywords": [
            "trash"
        ]
    },
    {
        "name": "pi pi-external-link",
        "label": "External Link",
        "category": "navegacion",
        "keywords": [
            "external",
            "link"
        ]
    },
    {
        "name": "pi pi-window-maximize",
        "label": "Window Maximize",
        "category": "contenido",
        "keywords": [
            "window",
            "maximize"
        ]
    },
    {
        "name": "pi pi-window-minimize",
        "label": "Window Minimize",
        "category": "contenido",
        "keywords": [
            "window",
            "minimize"
        ]
    },
    {
        "name": "pi pi-refresh",
        "label": "Refresh",
        "category": "acciones",
        "keywords": [
            "refresh"
        ]
    },
    {
        "name": "pi pi-user",
        "label": "User",
        "category": "social",
        "keywords": [
            "user"
        ]
    },
    {
        "name": "pi pi-exclamation-triangle",
        "label": "Exclamation Triangle",
        "category": "navegacion",
        "keywords": [
            "exclamation",
            "triangle"
        ]
    },
    {
        "name": "pi pi-calendar",
        "label": "Calendar",
        "category": "info",
        "keywords": [
            "calendar"
        ]
    },
    {
        "name": "pi pi-chevron-circle-left",
        "label": "Chevron Circle Left",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "circle",
            "left"
        ]
    },
    {
        "name": "pi pi-chevron-circle-down",
        "label": "Chevron Circle Down",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "circle",
            "down"
        ]
    },
    {
        "name": "pi pi-chevron-circle-right",
        "label": "Chevron Circle Right",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "circle",
            "right"
        ]
    },
    {
        "name": "pi pi-chevron-circle-up",
        "label": "Chevron Circle Up",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "circle",
            "up"
        ]
    },
    {
        "name": "pi pi-angle-double-down",
        "label": "Angle Double Down",
        "category": "navegacion",
        "keywords": [
            "angle",
            "double",
            "down"
        ]
    },
    {
        "name": "pi pi-angle-double-left",
        "label": "Angle Double Left",
        "category": "navegacion",
        "keywords": [
            "angle",
            "double",
            "left"
        ]
    },
    {
        "name": "pi pi-angle-double-right",
        "label": "Angle Double Right",
        "category": "navegacion",
        "keywords": [
            "angle",
            "double",
            "right"
        ]
    },
    {
        "name": "pi pi-angle-double-up",
        "label": "Angle Double Up",
        "category": "navegacion",
        "keywords": [
            "angle",
            "double",
            "up"
        ]
    },
    {
        "name": "pi pi-angle-down",
        "label": "Angle Down",
        "category": "navegacion",
        "keywords": [
            "angle",
            "down"
        ]
    },
    {
        "name": "pi pi-angle-left",
        "label": "Angle Left",
        "category": "navegacion",
        "keywords": [
            "angle",
            "left"
        ]
    },
    {
        "name": "pi pi-angle-right",
        "label": "Angle Right",
        "category": "navegacion",
        "keywords": [
            "angle",
            "right"
        ]
    },
    {
        "name": "pi pi-angle-up",
        "label": "Angle Up",
        "category": "navegacion",
        "keywords": [
            "angle",
            "up"
        ]
    },
    {
        "name": "pi pi-upload",
        "label": "Upload",
        "category": "acciones",
        "keywords": [
            "upload"
        ]
    },
    {
        "name": "pi pi-download",
        "label": "Download",
        "category": "acciones",
        "keywords": [
            "download"
        ]
    },
    {
        "name": "pi pi-ban",
        "label": "Ban",
        "category": "info",
        "keywords": [
            "ban"
        ]
    },
    {
        "name": "pi pi-star-fill",
        "label": "Star Fill",
        "category": "social",
        "keywords": [
            "star",
            "fill"
        ]
    },
    {
        "name": "pi pi-star",
        "label": "Star",
        "category": "social",
        "keywords": [
            "star"
        ]
    },
    {
        "name": "pi pi-chevron-left",
        "label": "Chevron Left",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "left"
        ]
    },
    {
        "name": "pi pi-chevron-right",
        "label": "Chevron Right",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "right"
        ]
    },
    {
        "name": "pi pi-chevron-down",
        "label": "Chevron Down",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "down"
        ]
    },
    {
        "name": "pi pi-chevron-up",
        "label": "Chevron Up",
        "category": "navegacion",
        "keywords": [
            "chevron",
            "up"
        ]
    },
    {
        "name": "pi pi-caret-left",
        "label": "Caret Left",
        "category": "navegacion",
        "keywords": [
            "caret",
            "left"
        ]
    },
    {
        "name": "pi pi-caret-right",
        "label": "Caret Right",
        "category": "navegacion",
        "keywords": [
            "caret",
            "right"
        ]
    },
    {
        "name": "pi pi-caret-down",
        "label": "Caret Down",
        "category": "navegacion",
        "keywords": [
            "caret",
            "down"
        ]
    },
    {
        "name": "pi pi-caret-up",
        "label": "Caret Up",
        "category": "navegacion",
        "keywords": [
            "caret",
            "up"
        ]
    },
    {
        "name": "pi pi-search",
        "label": "Search",
        "category": "acciones",
        "keywords": [
            "search"
        ]
    },
    {
        "name": "pi pi-check",
        "label": "Check",
        "category": "acciones",
        "keywords": [
            "check"
        ]
    },
    {
        "name": "pi pi-check-circle",
        "label": "Check Circle",
        "category": "acciones",
        "keywords": [
            "check",
            "circle"
        ]
    },
    {
        "name": "pi pi-times",
        "label": "Times",
        "category": "acciones",
        "keywords": [
            "times"
        ]
    },
    {
        "name": "pi pi-times-circle",
        "label": "Times Circle",
        "category": "acciones",
        "keywords": [
            "times",
            "circle"
        ]
    },
    {
        "name": "pi pi-plus",
        "label": "Plus",
        "category": "acciones",
        "keywords": [
            "plus"
        ]
    },
    {
        "name": "pi pi-plus-circle",
        "label": "Plus Circle",
        "category": "acciones",
        "keywords": [
            "plus",
            "circle"
        ]
    },
    {
        "name": "pi pi-minus",
        "label": "Minus",
        "category": "acciones",
        "keywords": [
            "minus"
        ]
    },
    {
        "name": "pi pi-minus-circle",
        "label": "Minus Circle",
        "category": "acciones",
        "keywords": [
            "minus",
            "circle"
        ]
    },
    {
        "name": "pi pi-circle-on",
        "label": "Circle On",
        "category": "info",
        "keywords": [
            "circle",
            "on"
        ]
    },
    {
        "name": "pi pi-circle-off",
        "label": "Circle Off",
        "category": "info",
        "keywords": [
            "circle",
            "off"
        ]
    },
    {
        "name": "pi pi-sort-down",
        "label": "Sort Down",
        "category": "acciones",
        "keywords": [
            "sort",
            "down"
        ]
    },
    {
        "name": "pi pi-sort-up",
        "label": "Sort Up",
        "category": "acciones",
        "keywords": [
            "sort",
            "up"
        ]
    },
    {
        "name": "pi pi-sort",
        "label": "Sort",
        "category": "acciones",
        "keywords": [
            "sort"
        ]
    },
    {
        "name": "pi pi-step-backward",
        "label": "Step Backward",
        "category": "navegacion",
        "keywords": [
            "step",
            "backward"
        ]
    },
    {
        "name": "pi pi-step-forward",
        "label": "Step Forward",
        "category": "navegacion",
        "keywords": [
            "step",
            "forward"
        ]
    },
    {
        "name": "pi pi-th-large",
        "label": "Th Large",
        "category": "acciones",
        "keywords": [
            "th",
            "large"
        ]
    },
    {
        "name": "pi pi-arrow-down",
        "label": "Arrow Down",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "down"
        ]
    },
    {
        "name": "pi pi-arrow-left",
        "label": "Arrow Left",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "left"
        ]
    },
    {
        "name": "pi pi-arrow-right",
        "label": "Arrow Right",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "right"
        ]
    },
    {
        "name": "pi pi-arrow-up",
        "label": "Arrow Up",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "up"
        ]
    },
    {
        "name": "pi pi-bars",
        "label": "Bars",
        "category": "navegacion",
        "keywords": [
            "bars"
        ]
    },
    {
        "name": "pi pi-arrow-circle-down",
        "label": "Arrow Circle Down",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "circle",
            "down"
        ]
    },
    {
        "name": "pi pi-arrow-circle-left",
        "label": "Arrow Circle Left",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "circle",
            "left"
        ]
    },
    {
        "name": "pi pi-arrow-circle-right",
        "label": "Arrow Circle Right",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "circle",
            "right"
        ]
    },
    {
        "name": "pi pi-arrow-circle-up",
        "label": "Arrow Circle Up",
        "category": "navegacion",
        "keywords": [
            "arrow",
            "circle",
            "up"
        ]
    },
    {
        "name": "pi pi-info",
        "label": "Info",
        "category": "info",
        "keywords": [
            "info"
        ]
    },
    {
        "name": "pi pi-info-circle",
        "label": "Info Circle",
        "category": "info",
        "keywords": [
            "info",
            "circle"
        ]
    },
    {
        "name": "pi pi-home",
        "label": "Home",
        "category": "navegacion",
        "keywords": [
            "home"
        ]
    },
    {
        "name": "pi pi-spinner",
        "label": "Spinner",
        "category": "navegacion",
        "keywords": [
            "spinner"
        ]
    }
];

const categoriasTabs = [
    { id: 'todos', label: 'Todos los Iconos' },
    { id: 'navegacion', label: 'Navegación' },
    { id: 'acciones', label: 'Acciones' },
    { id: 'contenido', label: 'Contenido' },
    { id: 'social', label: 'Social & Usuario' },
    { id: 'negocio', label: 'Negocio & Tienda' },
    { id: 'info', label: 'Info & Sistema' },
];

const iconosFiltrados = computed(() => {
    return catalogoIconos.filter(item => {
        const coincideCategoria = categoriaActiva.value === 'todos' || item.category === categoriaActiva.value;
        if (!coincideCategoria) return false;

        if (!busqueda.value.trim()) return true;

        const q = busqueda.value.toLowerCase().trim();
        return item.name.toLowerCase().includes(q) ||
               item.label.toLowerCase().includes(q) ||
               item.keywords.some(k => k.toLowerCase().includes(q));
    });
});

function seleccionarIcono(nombreIcono) {
    emit('update:modelValue', nombreIcono);
    modalAbierto.value = false;
}

function limpiarIcono(e) {
    e.stopPropagation();
    emit('update:modelValue', '');
}
</script>

<template>
    <div class="w-full">
        <!-- Etiqueta del Selector de Iconos -->
        <label v-if="label" class="block text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
            {{ label }}
        </label>

        <!-- Disparador Visual / Selector Principal -->
        <div
            @click="modalAbierto = true"
            class="group relative flex items-center justify-between w-full bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg px-2.5 py-1.5 cursor-pointer hover:border-emerald-500 dark:hover:border-emerald-500 transition-all shadow-xs min-h-[38px]"
        >
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <!-- Previsualización de Icono Selección -->
                <div class="w-6 h-6 rounded bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                    <i v-if="modelValue" :class="modelValue" class="text-xs" />
                    <i v-else class="pi pi-image text-zinc-400 text-xs" />
                </div>

                <div class="min-w-0 flex-1 truncate">
                    <span v-if="modelValue" class="block text-xs font-semibold text-zinc-900 dark:text-zinc-100 font-mono truncate">
                        {{ modelValue }}
                    </span>
                    <span v-else class="block text-xs text-zinc-400 dark:text-zinc-500 italic truncate">
                        {{ placeholder }}
                    </span>
                </div>
            </div>

            <!-- Botones de Acción / Disparador -->
            <div class="flex items-center gap-1 shrink-0 ml-1">
                <button
                    v-if="modelValue"
                    type="button"
                    @click="limpiarIcono"
                    v-tooltip.top="'Quitar'"
                    class="p-0.5 text-zinc-400 hover:text-red-500 dark:hover:text-red-400 rounded-full transition-colors"
                >
                    <i class="pi pi-times text-[10px]" />
                </button>
                <div class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 text-[11px] font-medium rounded group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                    Examinar
                </div>
            </div>
        </div>

        <!-- Diálogo Modal de Selección Visual de Iconos -->
        <Dialog
            v-model:visible="modalAbierto"
            header="Seleccionar Icono Visual"
            :modal="true"
            class="w-full max-w-3xl"
            :breakpoints="{ '960px': '75vw', '641px': '92vw' }"
        >
            <div class="space-y-4 pt-2">
                <!-- Buscador de Iconos -->
                <div class="relative">
                    <IconField class="w-full">
                        <InputIcon class="pi pi-search" />
                        <InputText
                            v-model="busqueda"
                            placeholder="Buscar por palabra clave (ej: inicio, usuario, carpeta, estrella, correo...)"
                            class="w-full text-sm"
                        />
                    </IconField>
                    <button
                        v-if="busqueda"
                        @click="busqueda = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs z-10 cursor-pointer"
                    >
                        Limpiar
                    </button>
                </div>

                <!-- Tabs de Categorías -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none border-b border-zinc-200 dark:border-zinc-700/80">
                    <button
                        v-for="cat in categoriasTabs"
                        :key="cat.id"
                        type="button"
                        @click="categoriaActiva = cat.id"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors whitespace-nowrap cursor-pointer',
                            categoriaActiva === cat.id
                                ? 'bg-emerald-600 text-white shadow-xs'
                                : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700',
                        ]"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Grid Visual de Iconos -->
                <div class="max-h-[380px] overflow-y-auto p-2 border border-zinc-200 dark:border-zinc-800 rounded-xl bg-zinc-50/50 dark:bg-zinc-900/40">
                    <div v-if="iconosFiltrados.length" class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-2">
                        <button
                            v-for="item in iconosFiltrados"
                            :key="item.name"
                            type="button"
                            @click="seleccionarIcono(item.name)"
                            v-tooltip.top="item.label"
                            :class="[
                                'group flex flex-col items-center justify-center p-2.5 sm:p-3 rounded-xl border transition-all cursor-pointer relative min-h-[72px]',
                                modelValue === item.name
                                    ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-500 text-emerald-600 dark:text-emerald-400 ring-2 ring-emerald-500/20 shadow-xs'
                                    : 'bg-white dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-emerald-400 hover:scale-105 hover:shadow-sm',
                            ]"
                        >
                            <i :class="item.name" class="!static text-2xl mb-1.5 transition-transform group-hover:scale-110 shrink-0" />
                            <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 truncate w-full text-center group-hover:text-emerald-600 dark:group-hover:text-emerald-400">
                                {{ item.name.replace('pi pi-', '') }}
                            </span>

                            <div v-if="modelValue === item.name" class="absolute top-1 right-1 w-2 h-2 rounded-full bg-emerald-500" />
                        </button>
                    </div>

                    <!-- Estado sin resultados -->
                    <div v-else class="text-center py-12 text-zinc-400">
                        <i class="pi pi-search text-3xl mb-2 text-zinc-300 dark:text-zinc-600" />
                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">No se encontraron iconos</p>
                        <p class="text-xs text-zinc-400 mt-0.5">Prueba con otra palabra clave en el buscador.</p>
                    </div>
                </div>

                <!-- Footer del Diálogo -->
                <div class="flex items-center justify-between pt-3 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                        {{ iconosFiltrados.length }} iconos disponibles
                    </span>

                    <div class="flex gap-2">
                        <Button
                            v-if="modelValue"
                            label="Sin Icono"
                            severity="secondary"
                            size="small"
                            @click="seleccionarIcono('')"
                        />
                        <Button
                            label="Cerrar"
                            severity="emerald"
                            size="small"
                            @click="modalAbierto = false"
                        />
                    </div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

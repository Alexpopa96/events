import {
    CameraIcon,
    VideoCameraIcon,
    MusicalNoteIcon,
    MicrophoneIcon,
    BuildingStorefrontIcon,
    PaintBrushIcon,
    SparklesIcon,
    CakeIcon,
    TruckIcon,
    SpeakerWaveIcon,
    LightBulbIcon,
    SwatchIcon,
    ScissorsIcon,
    EnvelopeIcon,
    HomeModernIcon,
    TagIcon,
} from '@heroicons/vue/24/outline';

const categoryIcons = {
    'fotograf': CameraIcon,
    'videograf': VideoCameraIcon,
    'dj': MusicalNoteIcon,
    'formatie': MusicalNoteIcon,
    'mc': MicrophoneIcon,
    'wedding-planner': SparklesIcon,
    'restaurant': BuildingStorefrontIcon,
    'salon-evenimente': BuildingStorefrontIcon,
    'decor': PaintBrushIcon,
    'florist': SparklesIcon,
    'torturi': CakeIcon,
    'candy-bar': CakeIcon,
    'cabina-foto': CameraIcon,
    'cabina-360': CameraIcon,
    'limuzine': TruckIcon,
    'sonorizare': SpeakerWaveIcon,
    'lumini': LightBulbIcon,
    'machiaj': SwatchIcon,
    'coafura': ScissorsIcon,
    'invitatii': EnvelopeIcon,
    'cazare': HomeModernIcon,
};

export function categoryIcon(slug) {
    return categoryIcons[slug] ?? TagIcon;
}

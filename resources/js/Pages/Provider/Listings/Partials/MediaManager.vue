<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { CloudArrowUpIcon, PhotoIcon, StarIcon, TrashIcon, VideoCameraIcon } from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const toast = useToast();

const props = defineProps({
    listingId: { type: [Number, String], required: true },
    media: { type: Array, default: () => [] },
    limits: {
        type: Object,
        default: () => ({ max_photos: null, max_videos: null, photos_count: 0, videos_count: 0 }),
    },
});

const localMedia = ref([...props.media]);
watch(() => props.media, (value) => { localMedia.value = [...value]; });

const uploadForm = useForm({ photos: [], videos: [] });
const dragOver = ref(false);
const draggingId = ref(null);
const fileInput = ref(null);
const pendingCount = ref(0);
const mediaToDelete = ref(null);
const deleting = ref(false);

const reload = { preserveScroll: true, preserveState: true, only: ['listing', 'mediaLimits'] };

const photosRemaining = computed(() => props.limits.max_photos === null ? null : Math.max(0, props.limits.max_photos - props.limits.photos_count));
const videosRemaining = computed(() => props.limits.max_videos === null ? null : Math.max(0, props.limits.max_videos - props.limits.videos_count));
const videosAllowed = computed(() => props.limits.max_videos === null || props.limits.max_videos > 0);

const hintText = computed(() => {
    const parts = [`JPG/PNG, max 5MB${photosRemaining.value === null ? '' : ` — ${photosRemaining.value} rămase`}`];

    if (videosAllowed.value) {
        parts.push(`MP4/MOV, max 50MB${videosRemaining.value === null ? '' : ` — ${videosRemaining.value} rămase`}`);
    }

    return parts.join(' · ');
});

function openPicker() {
    fileInput.value?.click();
}

function upload(files) {
    const list = Array.from(files ?? []);
    if (!list.length) return;

    const photos = list.filter((file) => file.type.startsWith('image/'));
    const videos = videosAllowed.value ? list.filter((file) => file.type.startsWith('video/')) : [];

    if (!photos.length && !videos.length) return;

    pendingCount.value = photos.length + videos.length;

    uploadForm.transform(() => ({ photos, videos }))
        .post(route('provider.listings.media.store', props.listingId), {
            ...reload,
            forceFormData: true,
            onSuccess: () => uploadForm.reset(),
            onError: () => toast.error('Încărcarea a eșuat. Încearcă din nou.'),
            onFinish: () => { pendingCount.value = 0; },
        });
}

function onFileChange(event) {
    upload(event.target.files);
    event.target.value = '';
}

function onDrop(event) {
    dragOver.value = false;
    if (draggingId.value !== null) return;
    upload(event.dataTransfer.files);
}

function setCover(media) {
    router.patch(route('provider.listings.media.cover', [props.listingId, media.id]), {}, reload);
}

function destroy(media) {
    mediaToDelete.value = media;
}

function confirmDestroy() {
    deleting.value = true;
    router.delete(route('provider.listings.media.destroy', [props.listingId, mediaToDelete.value.id]), {
        ...reload,
        onFinish: () => {
            deleting.value = false;
            mediaToDelete.value = null;
        },
    });
}

function onDragStart(media) {
    draggingId.value = media.id;
}

function onDragOverItem(target) {
    if (draggingId.value === null || draggingId.value === target.id) return;

    const from = localMedia.value.findIndex((item) => item.id === draggingId.value);
    const to = localMedia.value.findIndex((item) => item.id === target.id);
    if (from === -1 || to === -1) return;

    const reordered = [...localMedia.value];
    const [moved] = reordered.splice(from, 1);
    reordered.splice(to, 0, moved);
    localMedia.value = reordered;
}

function onDragEnd() {
    draggingId.value = null;
    router.patch(
        route('provider.listings.media.reorder', props.listingId),
        { order: localMedia.value.map((item) => item.id) },
        reload,
    );
}
</script>

<template>
    <div>
        <div
            class="relative rounded-2xl border-2 border-dashed px-6 py-8 text-center transition-colors duration-150 cursor-pointer"
            :class="dragOver ? 'border-brand-400 bg-brand-50/60' : 'border-line hover:border-brand-300 hover:bg-paper/60'"
            @click="openPicker"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <input
                ref="fileInput"
                type="file"
                :accept="videosAllowed ? 'image/*,video/*' : 'image/*'"
                multiple
                class="hidden"
                @change="onFileChange"
            />
            <CloudArrowUpIcon class="w-8 h-8 text-brand-500 mx-auto mb-2" />
            <p class="text-sm font-medium text-ink">
                Trage {{ videosAllowed ? 'fotografii sau video' : 'fotografii' }} aici sau apasă pentru a alege
            </p>
            <p class="text-xs text-ink-soft mt-1">{{ hintText }}</p>

            <div v-if="uploadForm.progress" class="mt-3 h-1.5 w-full max-w-xs mx-auto rounded-full bg-line overflow-hidden">
                <div class="h-full bg-brand-500 transition-all duration-150" :style="{ width: uploadForm.progress.percentage + '%' }"></div>
            </div>
        </div>
        <p v-if="uploadForm.errors.photos" class="mt-2 text-sm text-red-500">{{ uploadForm.errors.photos }}</p>
        <p v-if="uploadForm.errors.videos" class="mt-2 text-sm text-red-500">{{ uploadForm.errors.videos }}</p>

        <div v-if="localMedia.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mt-5">
            <div
                v-for="item in localMedia"
                :key="item.id"
                draggable="true"
                @dragstart="onDragStart(item)"
                @dragover.prevent="onDragOverItem(item)"
                @dragend="onDragEnd"
                class="group relative aspect-square rounded-xl overflow-hidden border border-line bg-paper cursor-grab active:cursor-grabbing"
                :class="draggingId === item.id && 'opacity-40'"
            >
                <video v-if="item.type === 'video'" :src="item.url" class="w-full h-full object-cover" muted playsinline controls />
                <img v-else :src="item.url" class="w-full h-full object-cover" alt="" />

                <span v-if="item.type === 'video'" class="absolute top-2 right-2 inline-flex items-center gap-1 rounded-full bg-ink/70 text-white text-[11px] font-semibold px-2 py-0.5 shadow-sm pointer-events-none">
                    <VideoCameraIcon class="w-3 h-3" /> Video
                </span>

                <span v-if="item.is_cover" class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-gold-500 text-white text-[11px] font-semibold px-2 py-0.5 shadow-sm">
                    <StarIconSolid class="w-3 h-3" /> Principală
                </span>

                <div class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1.5 bg-ink/50 p-1.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                    <button
                        v-if="item.type !== 'video' && !item.is_cover"
                        type="button"
                        @click.stop="setCover(item)"
                        title="Fă fotografia principală"
                        class="p-2 rounded-lg bg-white/95 text-ink-soft transition-colors duration-150 hover:text-gold-500"
                    >
                        <StarIcon class="w-4 h-4" />
                    </button>
                    <button
                        type="button"
                        @click.stop="destroy(item)"
                        title="Șterge"
                        class="p-2 rounded-lg bg-white/95 text-ink-soft transition-colors duration-150 hover:text-rose-600"
                    >
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div
                v-for="n in pendingCount"
                :key="`pending-${n}`"
                class="relative aspect-square rounded-xl overflow-hidden border border-line bg-line/50 animate-pulse flex items-center justify-center"
            >
                <PhotoIcon class="w-6 h-6 text-ink-soft/30" />
            </div>
        </div>

        <div v-else-if="pendingCount" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mt-5">
            <div
                v-for="n in pendingCount"
                :key="`pending-${n}`"
                class="relative aspect-square rounded-xl overflow-hidden border border-line bg-line/50 animate-pulse flex items-center justify-center"
            >
                <PhotoIcon class="w-6 h-6 text-ink-soft/30" />
            </div>
        </div>

        <div v-else class="flex items-center gap-2 mt-4 text-xs text-ink-soft">
            <PhotoIcon class="w-4 h-4" /> Fără fotografii încă — anunțurile cu fotografii primesc mai multe cereri de ofertă.
        </div>

        <ConfirmDialog
            :show="!!mediaToDelete"
            @update:show="(v) => !v && (mediaToDelete = null)"
            title="Ștergi acest fișier?"
            message="Fișierul va fi eliminat definitiv din anunț."
            confirm-label="Șterge"
            :processing="deleting"
            @confirm="confirmDestroy"
        />
    </div>
</template>

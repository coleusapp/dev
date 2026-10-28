import { onBeforeUnmount, ref, shallowRef } from 'vue';

export type RecordingKind = 'camera' | 'screen' | 'audio';

/**
 * Container types tried in order; the first the browser can record wins
 * (Chrome/Firefox: WebM, Safari: MP4).
 */
const TYPES: Record<RecordingKind, string[]> = {
    camera: ['video/webm;codecs=vp9,opus', 'video/webm', 'video/mp4'],
    screen: ['video/webm;codecs=vp9,opus', 'video/webm', 'video/mp4'],
    audio: ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'],
};

const LABELS: Record<RecordingKind, string> = { camera: 'Video', screen: 'Screen recording', audio: 'Voice memo' };

const pad = (value: number): string => String(value).padStart(2, '0');

/**
 * The file name for a recording: what it is, when, and an ending the
 * players recognise (voice memos in WebM end in .weba so they get an
 * audio player rather than a video one).
 */
export const recordingName = (kind: RecordingKind, mimeType: string, at: Date = new Date()): string => {
    const stamp = `${at.getFullYear()}-${pad(at.getMonth() + 1)}-${pad(at.getDate())} ${pad(at.getHours())}.${pad(at.getMinutes())}.${pad(at.getSeconds())}`;
    const ending = mimeType.startsWith('audio/mp4')
        ? 'm4a'
        : mimeType.startsWith('audio/')
          ? 'weba'
          : mimeType.startsWith('video/mp4')
            ? 'mp4'
            : 'webm';

    return `${LABELS[kind]} ${stamp}.${ending}`;
};

/**
 * Records from the camera, the screen or the microphone in the browser
 * (O3.1f / O3.6) with MediaRecorder. The live stream is exposed for a
 * preview; stop() resolves with the recording as a File, ready to attach.
 * Devices are released when recording stops, is cancelled, or the
 * component goes away.
 */
export const useRecorder = () => {
    const state = ref<'idle' | 'starting' | 'recording'>('idle');
    const error = ref<string | null>(null);
    const elapsed = ref(0);
    const stream = shallowRef<MediaStream | null>(null);
    let recorder: MediaRecorder | null = null;
    let chunks: Blob[] = [];
    let kind: RecordingKind = 'camera';
    let timer: ReturnType<typeof setInterval> | undefined;
    let finished: ((file: File | null) => void) | null = null;

    const release = (): void => {
        clearInterval(timer);
        stream.value?.getTracks().forEach((track) => track.stop());
        stream.value = null;
        recorder = null;
        state.value = 'idle';
    };

    const supported = (): boolean => typeof MediaRecorder !== 'undefined' && !!navigator.mediaDevices;

    const start = async (what: RecordingKind): Promise<void> => {
        if (state.value !== 'idle') {
            return;
        }

        if (!supported()) {
            error.value = "This browser can't record.";
            return;
        }

        kind = what;
        error.value = null;
        state.value = 'starting';

        try {
            stream.value =
                what === 'screen'
                    ? await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true })
                    : await navigator.mediaDevices.getUserMedia(what === 'audio' ? { audio: true } : { video: true, audio: true });
        } catch (exception) {
            state.value = 'idle';
            error.value =
                exception instanceof DOMException && exception.name === 'NotAllowedError'
                    ? 'Recording was not allowed.'
                    : "Couldn't start recording.";
            return;
        }

        const mimeType = TYPES[what].find((type) => MediaRecorder.isTypeSupported(type)) ?? '';
        recorder = new MediaRecorder(stream.value, mimeType ? { mimeType } : undefined);
        chunks = [];
        recorder.ondataavailable = (event) => event.data.size && chunks.push(event.data);
        recorder.onstop = () => {
            const type = (recorder?.mimeType || mimeType || 'video/webm').split(';')[0];
            const file = chunks.length ? new File(chunks, recordingName(kind, type), { type }) : null;
            release();
            finished?.(file);
            finished = null;
        };
        // Ending the screen share from the browser's own bar stops the recording too.
        stream.value.getVideoTracks()[0]?.addEventListener('ended', () => void stop());

        elapsed.value = 0;
        timer = setInterval(() => elapsed.value++, 1000);
        recorder.start(1000);
        state.value = 'recording';
    };

    /**
     * Stops and gives back the recording (null when nothing was recorded).
     */
    const stop = (): Promise<File | null> =>
        new Promise((resolve) => {
            if (!recorder || recorder.state === 'inactive') {
                resolve(null);
                return;
            }

            finished = resolve;
            recorder.stop();
        });

    const cancel = (): void => {
        finished = null;

        if (recorder && recorder.state !== 'inactive') {
            recorder.onstop = null;
            recorder.stop();
        }

        release();
    };

    onBeforeUnmount(cancel);

    return { state, error, elapsed, stream, start, stop, cancel, supported };
};

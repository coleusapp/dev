// @vitest-environment jsdom
import { recordingName, useRecorder } from '@/composables/useRecorder';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, defineComponent } from 'vue';

class FakeRecorder {
    static isTypeSupported = (type: string) => type === 'video/webm' || type === 'audio/webm';
    state: 'inactive' | 'recording' = 'inactive';
    ondataavailable: ((event: { data: Blob }) => void) | null = null;
    onstop: (() => void) | null = null;

    constructor(
        public stream: unknown,
        public options?: { mimeType: string },
    ) {}

    get mimeType(): string {
        return this.options?.mimeType ?? '';
    }

    start(): void {
        this.state = 'recording';
        this.ondataavailable?.({ data: new Blob(['frame']) });
    }

    stop(): void {
        this.state = 'inactive';
        this.onstop?.();
    }
}

const track = { stop: vi.fn(), addEventListener: vi.fn() };
const fakeStream = { getTracks: () => [track], getVideoTracks: () => [track] };

beforeEach(() => {
    vi.useFakeTimers();
    vi.stubGlobal('MediaRecorder', FakeRecorder);
    Object.defineProperty(navigator, 'mediaDevices', {
        configurable: true,
        value: { getUserMedia: vi.fn(async () => fakeStream), getDisplayMedia: vi.fn(async () => fakeStream) },
    });
    track.stop.mockClear();
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

const mount = () => {
    let recorder!: ReturnType<typeof useRecorder>;
    createApp(defineComponent({ setup: () => ((recorder = useRecorder()), () => null) })).mount(document.createElement('div'));

    return recorder;
};

describe('recordingName', () => {
    it('names recordings by kind and time, with an ending players recognise', () => {
        const at = new Date(2026, 9, 28, 14, 5, 9);

        expect(recordingName('camera', 'video/webm', at)).toBe('Video 2026-10-28 14.05.09.webm');
        expect(recordingName('screen', 'video/mp4', at)).toBe('Screen recording 2026-10-28 14.05.09.mp4');
        expect(recordingName('audio', 'audio/webm', at)).toBe('Voice memo 2026-10-28 14.05.09.weba');
        expect(recordingName('audio', 'audio/mp4', at)).toBe('Voice memo 2026-10-28 14.05.09.m4a');
    });
});

describe('useRecorder', () => {
    it('records the camera, counts time and gives back a file, releasing the camera', async () => {
        const recorder = mount();

        await recorder.start('camera');
        expect(recorder.state.value).toBe('recording');
        expect(navigator.mediaDevices.getUserMedia).toHaveBeenCalledWith({ video: true, audio: true });

        vi.advanceTimersByTime(3000);
        expect(recorder.elapsed.value).toBe(3);

        const file = await recorder.stop();
        expect(file?.name).toMatch(/^Video .+\.webm$/);
        expect(file?.type).toBe('video/webm');
        expect(track.stop).toHaveBeenCalled();
        expect(recorder.state.value).toBe('idle');
    });

    it('records the screen with getDisplayMedia, and cancelling gives nothing', async () => {
        const recorder = mount();

        await recorder.start('screen');
        expect(navigator.mediaDevices.getDisplayMedia).toHaveBeenCalled();

        recorder.cancel();
        expect(recorder.state.value).toBe('idle');
        expect(await recorder.stop()).toBeNull();
    });

    it('explains a refused permission', async () => {
        navigator.mediaDevices.getUserMedia = vi.fn(async () => {
            throw new DOMException('no', 'NotAllowedError');
        });
        const recorder = mount();

        await recorder.start('audio');

        expect(recorder.state.value).toBe('idle');
        expect(recorder.error.value).toBe('Recording was not allowed.');
    });
});

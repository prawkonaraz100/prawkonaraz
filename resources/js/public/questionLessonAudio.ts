type PlaybackMode = 'all' | 'section';

const POLISH_LANGUAGE = 'pl-PL';
const DEFAULT_SPEECH_RATE = 0.9;
const SECTION_PAUSE_MS = 650;
const SPEECH_START_DELAY_MS = 80;
const CANCELED_ERRORS = new Set(['canceled', 'interrupted']);

export const normalizeLessonAudioText = (value: string): string =>
    value
        .replace(/\u00a0/g, ' ')
        .replace(/[ \t]+/g, ' ')
        .replace(/\s*[\r\n]+\s*/g, '. ')
        .replace(/([.!?])\s*\.+/g, '$1 ')
        .replace(/\s{2,}/g, ' ')
        .trim();

export const resolvePolishVoice = (
    voices: SpeechSynthesisVoice[],
): SpeechSynthesisVoice | null => {
    const polishVoices = voices.filter((voice) => {
        const language = voice.lang.toLowerCase();
        const name = voice.name.toLowerCase();

        return language === 'pl-pl'
            || language.startsWith('pl')
            || name.includes('polski')
            || name.includes('polish')
            || name.includes('pl-pl');
    });

    const hasPolishName = (voice: SpeechSynthesisVoice) => {
        const name = voice.name.toLowerCase();

        return name.includes('polski') || name.includes('polish') || name.includes('pl-pl');
    };

    const scoreVoice = (voice: SpeechSynthesisVoice): number => {
        const language = voice.lang.toLowerCase();
        const name = voice.name.toLowerCase();
        let score = 0;

        if (language === 'pl-pl') {
            score += 100;
        } else if (language.startsWith('pl')) {
            score += 80;
        } else if (hasPolishName(voice)) {
            score += 50;
        }

        if (name.includes('natural')) {
            score += 35;
        }

        if (name.includes('zofia')) {
            score += 30;
        }

        if (name.includes('paulina')) {
            score += 25;
        }

        if (name.includes('agnieszka')) {
            score += 20;
        }

        if (name.includes('online')) {
            score += 10;
        }

        if (voice.localService) {
            score += 5;
        }

        if (name.includes('adam')) {
            score -= 20;
        }

        return score;
    };

    return polishVoices
        .map((voice, index) => ({ voice, index, score: scoreVoice(voice) }))
        .sort((left, right) => right.score - left.score || left.index - right.index)[0]?.voice
        ?? null;
};

const readElementText = (element: Element): string => {
    const clone = element.cloneNode(true) as HTMLElement;

    clone
        .querySelectorAll(
            [
                'a',
                'audio',
                'button',
                'input',
                'script',
                'select',
                'style',
                'svg',
                'template',
                'textarea',
                'video',
                '[aria-hidden="true"]',
                '[data-lesson-audio-ignore]',
            ].join(','),
        )
        .forEach((node) => node.remove());

    return normalizeLessonAudioText(clone.textContent ?? '');
};

const sectionText = (section: HTMLElement): string => {
    const title = section.dataset.lessonAudioTitle ?? '';
    const contentTargets = Array.from(
        section.querySelectorAll<HTMLElement>('[data-lesson-audio-content]'),
    );
    const content = contentTargets.length > 0
        ? contentTargets.map(readElementText).join('. ')
        : readElementText(section);

    return normalizeLessonAudioText([title, content].filter(Boolean).join('. '));
};

const setButtonLabel = (button: HTMLButtonElement, label: string) => {
    const labelTarget = button.querySelector<HTMLElement>('[data-lesson-audio-label]');

    if (labelTarget) {
        labelTarget.textContent = label;
    } else {
        button.textContent = label;
    }
};

const setButtonAccessibleLabel = (
    button: HTMLButtonElement,
    label: string,
    title: string = label,
) => {
    button.setAttribute('aria-label', label);
    button.setAttribute('title', title);
};

const pauseQuestionAudio = () => {
    document
        .querySelectorAll<HTMLAudioElement>('[data-question-audio-player]')
        .forEach((audio) => {
            if (!audio.paused) {
                audio.pause();
            }
        });
};

const createUtterance = (
    text: string,
    voices: SpeechSynthesisVoice[],
): SpeechSynthesisUtterance => {
    const utterance = new SpeechSynthesisUtterance(text);

    utterance.lang = POLISH_LANGUAGE;
    utterance.rate = DEFAULT_SPEECH_RATE;
    utterance.pitch = 1;
    utterance.volume = 1;

    const voice = resolvePolishVoice(voices);

    if (voice) {
        utterance.voice = voice;
    }

    return utterance;
};

const setupQuestionLessonAudioRoot = (root: HTMLElement) => {
    const playAllButton = root.querySelector<HTMLButtonElement>(
        '[data-lesson-audio-play-all]',
    );
    const status = root.querySelector<HTMLElement>('[data-lesson-audio-status]');
    let sections = Array.from(
        root.querySelectorAll<HTMLElement>('[data-lesson-audio-section]'),
    ).filter((section) => sectionText(section) !== '');

    if (sections.length === 0) {
        root
            .querySelectorAll<HTMLElement>('[data-lesson-audio-play-all], [data-lesson-audio-section-button]')
            .forEach((button) => button.classList.add('hidden'));

        return;
    }

    if (!('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
        root
            .querySelectorAll<HTMLButtonElement>('[data-lesson-audio-play-all], [data-lesson-audio-section-button]')
            .forEach((button) => {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            });

        if (status) {
            status.textContent = 'Czytanie na głos nie jest obsługiwane w tej przeglądarce.';
        }

        return;
    }

    const synth = window.speechSynthesis;
    let voices = synth.getVoices();
    let isPlayingAll = false;
    let activeSection: HTMLElement | null = null;
    let activeMode: PlaybackMode | null = null;
    let activeUtterance: SpeechSynthesisUtterance | null = null;
    let playbackToken = 0;
    let pauseTimer: number | null = null;

    const refreshVoices = () => {
        voices = synth.getVoices();
    };

    synth.addEventListener('voiceschanged', refreshVoices);
    refreshVoices();

    const setStatus = (message: string) => {
        if (status) {
            status.textContent = message;
        }
    };

    const updateButtons = () => {
        const allIsActive = isPlayingAll;

        if (playAllButton) {
            const label = allIsActive
                ? playAllButton.dataset.stopLabel ?? 'Zatrzymaj wyjaśnienie'
                : playAllButton.dataset.playLabel ?? 'Odtwórz wyjaśnienie';
            const ariaLabel = allIsActive
                ? playAllButton.dataset.stopAriaLabel ?? label
                : playAllButton.dataset.playAriaLabel ?? label;

            setButtonLabel(playAllButton, label);
            setButtonAccessibleLabel(playAllButton, ariaLabel, ariaLabel);
            playAllButton.setAttribute('aria-pressed', String(allIsActive));
        }

        sections.forEach((section) => {
            const isActive = section === activeSection && activeUtterance !== null;
            const sectionButton = section.querySelector<HTMLButtonElement>(
                '[data-lesson-audio-section-button]',
            );

            section.classList.toggle('is-reading', isActive);

            if (!sectionButton) {
                return;
            }

            const buttonIsStop = isActive && activeMode === 'section';
            const label = buttonIsStop
                ? sectionButton.dataset.stopLabel ?? 'Zatrzymaj czytanie'
                : sectionButton.dataset.playLabel ?? 'Przeczytaj tę sekcję';
            const ariaLabel = buttonIsStop
                ? sectionButton.dataset.stopAriaLabel ?? label
                : sectionButton.dataset.playAriaLabel ?? label;

            setButtonLabel(sectionButton, label);
            setButtonAccessibleLabel(sectionButton, ariaLabel, ariaLabel);
            sectionButton.setAttribute('aria-pressed', String(buttonIsStop));
        });
    };

    const clearPauseTimer = () => {
        if (pauseTimer !== null) {
            window.clearTimeout(pauseTimer);
            pauseTimer = null;
        }
    };

    const clearActiveSection = () => {
        activeSection = null;
        activeMode = null;
        activeUtterance = null;
        updateButtons();
    };

    const stopPlayback = (message = '') => {
        playbackToken += 1;
        isPlayingAll = false;
        clearPauseTimer();
        synth.cancel();
        clearActiveSection();
        setStatus(message);
    };

    const speakSection = (
        section: HTMLElement,
        mode: PlaybackMode,
        token: number,
        onFinished: (completed: boolean) => void,
    ) => {
        const text = sectionText(section);

        if (text === '') {
            onFinished(false);

            return;
        }

        const utterance = createUtterance(text, voices);
        const title = section.dataset.lessonAudioTitle ?? 'sekcja';

        activeSection = section;
        activeMode = mode;
        activeUtterance = utterance;
        pauseQuestionAudio();
        setStatus(`Czytam: ${title}`);
        updateButtons();

        utterance.onend = () => {
            if (playbackToken !== token) {
                return;
            }

            clearActiveSection();
            onFinished(true);
        };

        utterance.onerror = (event) => {
            if (playbackToken !== token || CANCELED_ERRORS.has(event.error)) {
                return;
            }

            clearActiveSection();
            isPlayingAll = false;
            setStatus('Nie udało się dokończyć czytania tej sekcji.');
            onFinished(false);
        };

        window.setTimeout(() => {
            if (playbackToken === token) {
                synth.speak(utterance);
            }
        }, SPEECH_START_DELAY_MS);
    };

    const playAllFrom = (index: number, token: number) => {
        if (playbackToken !== token) {
            return;
        }

        if (index >= sections.length) {
            isPlayingAll = false;
            clearActiveSection();
            setStatus('Odtworzono całe wyjaśnienie.');

            return;
        }

        speakSection(sections[index], 'all', token, (completed) => {
            if (!completed || playbackToken !== token) {
                return;
            }

            pauseTimer = window.setTimeout(
                () => playAllFrom(index + 1, token),
                SECTION_PAUSE_MS,
            );
        });
    };

    playAllButton?.addEventListener('click', () => {
        if (isPlayingAll) {
            stopPlayback('Zatrzymano czytanie wyjaśnienia.');

            return;
        }

        stopPlayback();
        sections = sections.filter((section) => sectionText(section) !== '');

        if (sections.length === 0) {
            setStatus('Brak treści do przeczytania.');

            return;
        }

        isPlayingAll = true;
        const token = playbackToken;

        updateButtons();
        playAllFrom(0, token);
    });

    sections.forEach((section) => {
        const button = section.querySelector<HTMLButtonElement>(
            '[data-lesson-audio-section-button]',
        );

        button?.addEventListener('click', () => {
            if (activeSection === section && activeMode === 'section') {
                stopPlayback('Zatrzymano czytanie sekcji.');

                return;
            }

            stopPlayback();
            const token = playbackToken;

            speakSection(section, 'section', token, (completed) => {
                if (!completed || playbackToken !== token) {
                    return;
                }

                setStatus('Zakończono czytanie sekcji.');
            });
        });
    });

    document
        .querySelectorAll<HTMLButtonElement>('[data-question-audio-toggle]')
        .forEach((button) => {
            button.addEventListener('click', () => {
                if (activeUtterance !== null || synth.speaking) {
                    stopPlayback();
                }
            });
        });

    window.addEventListener('pagehide', () => stopPlayback(), { once: true });
    updateButtons();
};

export const setupQuestionLessonAudio = () => {
    document
        .querySelectorAll<HTMLElement>('[data-lesson-audio-root]')
        .forEach(setupQuestionLessonAudioRoot);
};

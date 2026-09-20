export async function assertNewsroomAccessibility(page, { surface, viewport }) {
    const structural = await page.evaluate(() => {
        const violations = [];
        const visible = (element) => {
            const style = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number(style.opacity) > 0
                && rect.width > 0
                && rect.height > 0;
        };

        const labelledByText = (element) => {
            const ids = (element.getAttribute('aria-labelledby') ?? '')
                .split(/\s+/)
                .filter(Boolean);

            return ids
                .map((id) => document.getElementById(id)?.textContent?.trim() ?? '')
                .filter(Boolean)
                .join(' ');
        };

        const accessibleName = (element) => {
            const ariaLabel = element.getAttribute('aria-label')?.trim();
            if (ariaLabel) return ariaLabel;

            const labelledBy = labelledByText(element);
            if (labelledBy) return labelledBy;

            if (element instanceof HTMLInputElement) {
                const labels = [...(element.labels ?? [])]
                    .map((label) => label.textContent?.trim() ?? '')
                    .filter(Boolean);
                if (labels.length) return labels.join(' ');
            }

            if (element instanceof HTMLImageElement) {
                return element.getAttribute('alt')?.trim() ?? '';
            }

            return element.textContent?.replace(/\s+/g, ' ').trim()
                || element.getAttribute('title')?.trim()
                || '';
        };

        const main = [...document.querySelectorAll('main')].filter(visible);
        if (main.length !== 1) {
            violations.push(`expected exactly one visible main landmark, found ${main.length}`);
        } else if (main[0].id !== 'main-content') {
            violations.push('main landmark must expose id="main-content" for the skip link');
        }

        const headers = [...document.querySelectorAll('header')].filter(visible);
        if (headers.length < 1) {
            violations.push('missing visible header landmark');
        }

        const footers = [...document.querySelectorAll('footer')].filter(visible);
        if (footers.length < 1) {
            violations.push('missing visible footer landmark');
        }

        const skipLinks = [...document.querySelectorAll('a[href="#main-content"]')].filter(visible);
        if (skipLinks.length !== 1) {
            violations.push(`expected one visible skip link, found ${skipLinks.length}`);
        } else if (!skipLinks[0].classList.contains('public-content-skip-link')) {
            violations.push('skip link is not using the public-content skip-link contract');
        }

        const focusables = [...document.querySelectorAll(
            'a[href], button:not([disabled]), summary, input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        )].filter(visible);

        if (focusables.length === 0) {
            violations.push('page has no visible keyboard-focusable element');
        } else if (skipLinks[0] && focusables[0] !== skipLinks[0]) {
            violations.push('skip link is not the first visible keyboard-focusable element');
        }

        const h1s = [...document.querySelectorAll('h1')].filter(visible);
        if (h1s.length !== 1) {
            violations.push(`expected exactly one visible H1, found ${h1s.length}`);
        }

        const headings = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6')].filter(visible);
        let previousLevel = null;
        for (const heading of headings) {
            const level = Number(heading.tagName.slice(1));
            if (previousLevel !== null && level > previousLevel + 1) {
                violations.push(
                    `heading level jumps from H${previousLevel} to H${level}: "${accessibleName(heading).slice(0, 80)}"`,
                );
            }
            previousLevel = level;
        }

        for (const image of [...document.querySelectorAll('img')].filter(visible)) {
            if (!image.hasAttribute('alt')) {
                violations.push(`image missing alt attribute: ${image.currentSrc || image.src || '(no src)'}`);
            }
        }

        for (const nav of [...document.querySelectorAll('nav')].filter(visible)) {
            if (!accessibleName(nav)) {
                violations.push('navigation landmark is missing an accessible name');
            }
        }

        const controls = [...document.querySelectorAll(
            'a[href], button:not([disabled]), summary, input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])',
        )].filter(visible);

        for (const control of controls) {
            if (!accessibleName(control)) {
                violations.push(`interactive control is missing an accessible name: <${control.tagName.toLowerCase()}>`);
            }
        }

        for (const control of [...document.querySelectorAll('input,select,textarea')].filter(visible)) {
            const hasLabel = Boolean(
                control.getAttribute('aria-label')?.trim()
                || control.getAttribute('aria-labelledby')?.trim()
                || (control.labels && control.labels.length > 0),
            );

            if (!hasLabel) {
                violations.push(
                    `form control is missing an associated label: ${control.getAttribute('name') || control.id || control.tagName.toLowerCase()}`,
                );
            }
        }

        for (const link of [...document.querySelectorAll('a[target="_blank"]')].filter(visible)) {
            const rel = new Set((link.getAttribute('rel') ?? '').split(/\s+/).filter(Boolean));
            if (!rel.has('noopener') || !rel.has('noreferrer')) {
                violations.push(`target=_blank link is missing noopener+noreferrer: ${link.getAttribute('href')}`);
            }
        }

        return {
            violations,
            headings: headings.length,
            images: [...document.querySelectorAll('img')].filter(visible).length,
            controls: controls.length,
            navs: [...document.querySelectorAll('nav')].filter(visible).length,
        };
    });

    if (structural.violations.length) {
        throw new Error(
            `[a11y:${surface}:${viewport}] structural violations: ${structural.violations.join(' | ')}`,
        );
    }

    const contrast = await page.evaluate(() => {
        const parseColor = (value) => {
            const match = value.match(/rgba?\(([^)]+)\)/i);
            if (!match) return null;
            const parts = match[1].split(',').map((part) => part.trim());
            if (parts.length < 3) return null;
            return {
                r: Number(parts[0]),
                g: Number(parts[1]),
                b: Number(parts[2]),
                a: parts.length > 3 ? Number(parts[3]) : 1,
            };
        };

        const blend = (front, back) => {
            const alpha = front.a ?? 1;
            return {
                r: Math.round(front.r * alpha + back.r * (1 - alpha)),
                g: Math.round(front.g * alpha + back.g * (1 - alpha)),
                b: Math.round(front.b * alpha + back.b * (1 - alpha)),
                a: 1,
            };
        };

        const luminance = ({ r, g, b }) => {
            const channel = (value) => {
                const normalized = value / 255;
                return normalized <= 0.03928
                    ? normalized / 12.92
                    : ((normalized + 0.055) / 1.055) ** 2.4;
            };
            return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
        };

        const ratio = (a, b) => {
            const l1 = luminance(a);
            const l2 = luminance(b);
            const lighter = Math.max(l1, l2);
            const darker = Math.min(l1, l2);
            return (lighter + 0.05) / (darker + 0.05);
        };

        const visible = (element) => {
            const style = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number(style.opacity) > 0
                && rect.width > 0
                && rect.height > 0;
        };

        const backgroundFor = (element) => {
            const chain = [];
            let current = element;
            while (current instanceof Element) {
                const style = window.getComputedStyle(current);
                if (style.backgroundImage && style.backgroundImage !== 'none') {
                    return null;
                }
                chain.push(style.backgroundColor);
                current = current.parentElement;
            }

            let background = { r: 255, g: 255, b: 255, a: 1 };
            for (const value of chain.reverse()) {
                const color = parseColor(value);
                if (color && color.a > 0) {
                    background = blend(color, background);
                }
            }
            return background;
        };

        const failures = [];
        let checked = 0;

        for (const element of [...document.querySelectorAll('body *')]) {
            if (!visible(element)) continue;
            if (element.closest('[aria-hidden="true"]')) continue;

            const directText = [...element.childNodes]
                .filter((node) => node.nodeType === Node.TEXT_NODE)
                .map((node) => node.textContent?.replace(/\s+/g, ' ').trim() ?? '')
                .filter(Boolean)
                .join(' ');

            if (!directText) continue;

            const style = window.getComputedStyle(element);
            const foregroundRaw = parseColor(style.color);
            const background = backgroundFor(element);
            if (!foregroundRaw || !background) continue;

            const foreground = blend(foregroundRaw, background);
            const fontSize = Number.parseFloat(style.fontSize || '16');
            const fontWeight = Number.parseInt(style.fontWeight || '400', 10) || 400;
            const large = fontSize >= 24 || (fontSize >= 18.66 && fontWeight >= 700);
            const minimum = large ? 3 : 4.5;
            const actual = ratio(foreground, background);
            checked += 1;

            if (actual + 0.01 < minimum) {
                failures.push({
                    text: directText.slice(0, 90),
                    ratio: Number(actual.toFixed(2)),
                    minimum,
                    color: style.color,
                    background: `rgb(${background.r}, ${background.g}, ${background.b})`,
                });
            }

            if (failures.length >= 12) break;
        }

        return { checked, failures };
    });

    if (contrast.failures.length) {
        throw new Error(
            `[a11y:${surface}:${viewport}] contrast violations: ${JSON.stringify(contrast.failures)}`,
        );
    }

    const skipLink = page.locator('.public-content-skip-link:visible').first();
    const initialSkipBox = await skipLink.boundingBox();
    if (!initialSkipBox || initialSkipBox.y + initialSkipBox.height > 0) {
        throw new Error(
            `[a11y:${surface}:${viewport}] skip link must be visually hidden before keyboard focus`,
        );
    }

    const focusChecks = [
        ['skip-link', '.public-content-skip-link:visible'],
        ['header-menu', 'header summary:visible'],
        ['main-link', 'main a[href]:visible'],
    ];

    for (const [label, selector] of focusChecks) {
        const locator = page.locator(selector).first();
        if (await locator.count() === 0) continue;

        await locator.focus();
        const focusStyle = await locator.evaluate((element) => {
            const style = window.getComputedStyle(element);
            return {
                outlineStyle: style.outlineStyle,
                outlineWidth: Number.parseFloat(style.outlineWidth || '0'),
                boxShadow: style.boxShadow,
            };
        });

        const hasOutline = focusStyle.outlineStyle !== 'none' && focusStyle.outlineWidth >= 2;
        const hasBoxShadow = focusStyle.boxShadow && focusStyle.boxShadow !== 'none';

        if (!hasOutline && !hasBoxShadow) {
            throw new Error(`[a11y:${surface}:${viewport}] ${label} has no visible focus indicator`);
        }
    }

    await skipLink.focus();
    await page.keyboard.press('Enter');
    await page.waitForTimeout(20);

    const skipResult = await page.evaluate(() => ({
        hash: window.location.hash,
        activeId: document.activeElement?.id ?? '',
    }));

    if (skipResult.hash !== '#main-content' || skipResult.activeId !== 'main-content') {
        throw new Error(
            `[a11y:${surface}:${viewport}] skip link did not move keyboard focus to main-content: ${JSON.stringify(skipResult)}`,
        );
    }

    await page.emulateMedia({ reducedMotion: 'reduce' });
    const motion = await page.evaluate(() => {
        const durationMs = (value) => value
            .split(',')
            .map((part) => part.trim())
            .map((part) => part.endsWith('ms')
                ? Number.parseFloat(part)
                : Number.parseFloat(part) * 1000)
            .filter(Number.isFinite);

        const offenders = [];
        let checked = 0;

        for (const element of [...document.querySelectorAll('body *')]) {
            const style = window.getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            if (
                style.display === 'none'
                || style.visibility === 'hidden'
                || rect.width === 0
                || rect.height === 0
            ) {
                continue;
            }

            checked += 1;
            const maxTransition = Math.max(0, ...durationMs(style.transitionDuration));
            const maxAnimation = Math.max(0, ...durationMs(style.animationDuration));

            if (maxTransition > 20 || maxAnimation > 20) {
                offenders.push({
                    tag: element.tagName.toLowerCase(),
                    className: typeof element.className === 'string' ? element.className.slice(0, 120) : '',
                    transition: style.transitionDuration,
                    animation: style.animationDuration,
                });
            }

            if (offenders.length >= 12) break;
        }

        return { checked, offenders };
    });
    await page.emulateMedia({ reducedMotion: 'no-preference' });

    await page.evaluate(() => {
        if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
    });

    const finalSkipBox = await skipLink.boundingBox();
    if (!finalSkipBox || finalSkipBox.y + finalSkipBox.height > 0) {
        throw new Error(
            `[a11y:${surface}:${viewport}] skip link remained visually exposed after accessibility checks`,
        );
    }

    if (motion.offenders.length) {
        throw new Error(
            `[a11y:${surface}:${viewport}] reduced-motion violations: ${JSON.stringify(motion.offenders)}`,
        );
    }

    return {
        status: 'ok',
        structural: {
            headings: structural.headings,
            images: structural.images,
            controls: structural.controls,
            navs: structural.navs,
        },
        contrast_checked: contrast.checked,
        motion_checked: motion.checked,
        manual_review_required: [
            'color-only meaning and non-text contrast',
            'visual review of focus visibility and reading order from screenshots',
        ],
    };
}

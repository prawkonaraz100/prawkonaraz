export async function assertNewsroomAccessibility(page, { surface, viewportName }) {
    const prefix = '[newsroom-a11y:' + surface + ':' + viewportName + ']';

    const audit = await page.evaluate(() => {
        const isVisible = (node) => {
            const style = getComputedStyle(node);
            const rect = node.getBoundingClientRect();

            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number.parseFloat(style.opacity || '1') > 0
                && rect.width > 0
                && rect.height > 0;
        };

        const authorProvidedName = (node) => {
            const ariaLabel = (node.getAttribute('aria-label') ?? '').trim();
            if (ariaLabel) return ariaLabel;

            return (node.getAttribute('aria-labelledby') ?? '')
                .split(/\s+/)
                .filter(Boolean)
                .map((id) => document.getElementById(id)?.textContent?.trim() ?? '')
                .filter(Boolean)
                .join(' ');
        };

        const accessibleName = (node) => {
            const authorName = authorProvidedName(node);
            if (authorName) return authorName;

            if ('labels' in node) {
                const labelText = Array.from(node.labels ?? [])
                    .map((label) => label.textContent?.trim() ?? '')
                    .filter(Boolean)
                    .join(' ');
                if (labelText) return labelText;
            }

            const text = (node.textContent ?? '').trim().replace(/\s+/g, ' ');
            if (text) return text;

            const imageAlt = node.querySelector?.('img[alt]')?.getAttribute('alt')?.trim();
            if (imageAlt) return imageAlt;

            const value = 'value' in node ? String(node.value ?? '').trim() : '';
            if (value) return value;

            return (node.getAttribute('title') ?? '').trim();
        };

        const visibleHeadings = Array.from(document.querySelectorAll('h1,h2,h3,h4,h5,h6'))
            .filter(isVisible)
            .map((node) => ({
                level: Number(node.tagName.slice(1)),
                text: (node.textContent ?? '').trim().replace(/\s+/g, ' ').slice(0, 120),
            }));

        const visibleAsides = Array.from(document.querySelectorAll('aside')).filter(isVisible);

        return {
            mainCount: document.querySelectorAll('main#main-content').length,
            skipCount: document.querySelectorAll('a[data-public-skip-link][href="#main-content"]').length,
            headerCount: document.querySelectorAll('header').length,
            footerCount: document.querySelectorAll('footer').length,
            navigationProblems: Array.from(document.querySelectorAll('nav'))
                .filter(isVisible)
                .filter((node) => !authorProvidedName(node))
                .map((node) => node.outerHTML.slice(0, 220)),
            complementaryProblems: visibleAsides.length <= 1
                ? []
                : visibleAsides
                    .filter((node) => !authorProvidedName(node))
                    .map((node) => node.outerHTML.slice(0, 220)),
            headings: visibleHeadings,
            imagesMissingAlt: Array.from(document.querySelectorAll('img:not([alt])'))
                .filter(isVisible)
                .map((node) => node.outerHTML.slice(0, 220)),
            unnamedControls: Array.from(document.querySelectorAll('a[href],button,summary,input:not([type="hidden"]),select,textarea'))
                .filter(isVisible)
                .filter((node) => !accessibleName(node))
                .map((node) => node.outerHTML.slice(0, 220)),
            unlabeledFields: Array.from(document.querySelectorAll('input:not([type="hidden"]),select,textarea'))
                .filter(isVisible)
                .filter((node) => {
                    const labels = 'labels' in node ? Array.from(node.labels ?? []) : [];
                    return !node.getAttribute('aria-label')
                        && !node.getAttribute('aria-labelledby')
                        && labels.every((label) => !(label.textContent ?? '').trim());
                })
                .map((node) => node.outerHTML.slice(0, 220)),
            invalidWithoutErrorAssociation: Array.from(document.querySelectorAll('[aria-invalid="true"]'))
                .filter(isVisible)
                .filter((node) => !node.getAttribute('aria-describedby') && !node.getAttribute('aria-errormessage'))
                .map((node) => node.outerHTML.slice(0, 220)),
        };
    });

    if (audit.mainCount !== 1) {
        throw new Error(prefix + ' expected exactly one main#main-content landmark.');
    }

    if (audit.skipCount !== 1) {
        throw new Error(prefix + ' missing the shared skip link.');
    }

    if (audit.headerCount < 1 || audit.footerCount < 1) {
        throw new Error(prefix + ' missing shared header/footer landmarks.');
    }

    if (audit.navigationProblems.length > 0) {
        throw new Error(prefix + ' unnamed nav landmarks: ' + audit.navigationProblems.join(' | '));
    }

    if (audit.complementaryProblems.length > 0) {
        throw new Error(prefix + ' multiple unnamed complementary landmarks: ' + audit.complementaryProblems.join(' | '));
    }

    const h1Count = audit.headings.filter((heading) => heading.level === 1).length;
    if (h1Count !== 1) {
        throw new Error(prefix + ' expected exactly one visible H1, got ' + h1Count + '.');
    }

    if (audit.headings[0]?.level !== 1) {
        throw new Error(prefix + ' first visible heading must be H1.');
    }

    for (let index = 1; index < audit.headings.length; index += 1) {
        if (audit.headings[index].level > audit.headings[index - 1].level + 1) {
            throw new Error(
                prefix + ' heading level jumps from H' + audit.headings[index - 1].level + ' "' + audit.headings[index - 1].text
                + '" to H' + audit.headings[index].level + ' "' + audit.headings[index].text + '".',
            );
        }
    }

    if (audit.imagesMissingAlt.length > 0) {
        throw new Error(prefix + ' visible images without alt: ' + audit.imagesMissingAlt.join(' | '));
    }

    if (audit.unnamedControls.length > 0) {
        throw new Error(prefix + ' visible interactive elements without accessible names: ' + audit.unnamedControls.join(' | '));
    }

    if (audit.unlabeledFields.length > 0) {
        throw new Error(prefix + ' visible form controls without labels: ' + audit.unlabeledFields.join(' | '));
    }

    if (audit.invalidWithoutErrorAssociation.length > 0) {
        throw new Error(prefix + ' invalid controls without error association: ' + audit.invalidWithoutErrorAssociation.join(' | '));
    }

    if (viewportName === '390' || viewportName === '1024') {
        await assertKeyboardFocus(page, prefix);
    }

    if (viewportName === '1024') {
        await assertReducedMotion(page, prefix);
    }

    console.log(prefix + ' PASS');
}

async function assertKeyboardFocus(page, prefix) {
    await page.evaluate(() => {
        if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }
        window.scrollTo(0, 0);
    });

    await page.keyboard.press('Tab');

    const firstFocus = await page.evaluate(() => {
        const active = document.activeElement;
        if (!(active instanceof HTMLElement)) return null;

        const style = getComputedStyle(active);
        const rect = active.getBoundingClientRect();
        const outlineWidth = Number.parseFloat(style.outlineWidth || '0');
        const outlineVisible = outlineWidth >= 1
            && style.outlineStyle !== 'none'
            && style.outlineColor !== 'transparent'
            && style.outlineColor !== 'rgba(0, 0, 0, 0)';

        return {
            skip: active.hasAttribute('data-public-skip-link'),
            visible: rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none',
            indicator: outlineVisible || (style.boxShadow && style.boxShadow !== 'none'),
        };
    });

    if (!firstFocus?.skip || !firstFocus.visible || !firstFocus.indicator) {
        throw new Error(prefix + ' first keyboard focus must be the visible skip link with a focus indicator.');
    }

    let summaryFocus = null;
    for (let step = 0; step < 40; step += 1) {
        await page.keyboard.press('Tab');

        summaryFocus = await page.evaluate(() => {
            const active = document.activeElement;
            if (!(active instanceof HTMLElement) || active.tagName !== 'SUMMARY') return null;

            const style = getComputedStyle(active);
            const outlineWidth = Number.parseFloat(style.outlineWidth || '0');
            const outlineVisible = outlineWidth >= 1
                && style.outlineStyle !== 'none'
                && style.outlineColor !== 'transparent'
                && style.outlineColor !== 'rgba(0, 0, 0, 0)';

            return {
                indicator: outlineVisible || (style.boxShadow && style.boxShadow !== 'none'),
                label: active.getAttribute('aria-label') ?? (active.textContent ?? '').trim(),
            };
        });

        if (summaryFocus) break;
    }

    if (!summaryFocus) {
        throw new Error(prefix + ' keyboard traversal did not reach the visible service-menu summary.');
    }

    if (!summaryFocus.indicator) {
        throw new Error(prefix + ' service-menu summary "' + summaryFocus.label + '" has no visible keyboard focus indicator.');
    }
}

async function assertReducedMotion(page, prefix) {
    await page.emulateMedia({ reducedMotion: 'reduce' });

    const result = await page.evaluate(() => {
        const isVisible = (node) => {
            const style = getComputedStyle(node);
            const rect = node.getBoundingClientRect();

            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number.parseFloat(style.opacity || '1') > 0
                && rect.width > 0
                && rect.height > 0;
        };

        const parseDuration = (value) => Math.max(...value.split(',').map((part) => {
            const trimmed = part.trim();
            if (trimmed.endsWith('ms')) return Number.parseFloat(trimmed);
            if (trimmed.endsWith('s')) return Number.parseFloat(trimmed) * 1000;
            return 0;
        }));

        const headerShell = document.querySelector('.home-site-header__shell');
        const headerTransitionMs = headerShell
            ? parseDuration(getComputedStyle(headerShell).transitionDuration)
            : null;

        const activeAnimations = Array.from(document.querySelectorAll('main *, header *, footer *'))
            .filter(isVisible)
            .map((node) => {
                const style = getComputedStyle(node);
                return {
                    node,
                    name: style.animationName,
                    duration: parseDuration(style.animationDuration),
                };
            })
            .filter((entry) => entry.name !== 'none' && entry.duration > 20)
            .slice(0, 10)
            .map((entry) => entry.node.outerHTML.slice(0, 180));

        return {
            mediaMatches: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            headerTransitionMs,
            activeAnimations,
        };
    });

    await page.emulateMedia({ reducedMotion: 'no-preference' });

    if (!result.mediaMatches) {
        throw new Error(prefix + ' reduced-motion media emulation is not active.');
    }

    if (result.headerTransitionMs === null || result.headerTransitionMs > 20) {
        throw new Error(prefix + ' header motion is not reduced: ' + result.headerTransitionMs + 'ms.');
    }

    if (result.activeAnimations.length > 0) {
        throw new Error(prefix + ' active animations remain under reduced motion: ' + result.activeAnimations.join(' | '));
    }
}

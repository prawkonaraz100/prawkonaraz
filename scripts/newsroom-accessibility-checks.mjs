export async function assertNewsroomAccessibility(page, { surface, viewportName }) {
    const prefix = '[newsroom-a11y:' + surface + ':' + viewportName + ']';

    const main = page.locator('main#main-content');
    if (await main.count() !== 1) {
        throw new Error(prefix + ' expected exactly one main#main-content landmark.');
    }

    const skipLink = page.locator('a[data-public-skip-link][href="#main-content"]');
    if (await skipLink.count() !== 1) {
        throw new Error(prefix + ' missing the shared skip link.');
    }

    for (const selector of ['header', 'footer']) {
        if (await page.locator(selector).count() < 1) {
            throw new Error(prefix + ' missing ' + selector + ' landmark.');
        }
    }

    const navigationProblems = await page.locator('nav').evaluateAll((nodes) => nodes
        .filter((node) => isVisible(node))
        .filter((node) => !accessibleName(node))
        .map((node) => node.outerHTML.slice(0, 220)));
    if (navigationProblems.length > 0) {
        throw new Error(prefix + ' unnamed nav landmarks: ' + navigationProblems.join(' | '));
    }

    const complementaryProblems = await page.locator('aside').evaluateAll((nodes) => {
        const visible = nodes.filter((node) => isVisible(node));
        if (visible.length <= 1) return [];

        return visible
            .filter((node) => !accessibleName(node))
            .map((node) => node.outerHTML.slice(0, 220));
    });
    if (complementaryProblems.length > 0) {
        throw new Error(prefix + ' multiple unnamed complementary landmarks: ' + complementaryProblems.join(' | '));
    }

    const headings = await page.locator('h1,h2,h3,h4,h5,h6').evaluateAll((nodes) => nodes
        .filter((node) => isVisible(node))
        .map((node) => ({
            level: Number(node.tagName.slice(1)),
            text: (node.textContent ?? '').trim().replace(/\s+/g, ' ').slice(0, 120),
        })));

    const h1Count = headings.filter((heading) => heading.level === 1).length;
    if (h1Count !== 1) {
        throw new Error(prefix + ' expected exactly one visible H1, got ' + h1Count + '.');
    }

    if (headings[0]?.level !== 1) {
        throw new Error(prefix + ' first visible heading must be H1.');
    }

    for (let index = 1; index < headings.length; index += 1) {
        if (headings[index].level > headings[index - 1].level + 1) {
            throw new Error(
                prefix + ' heading level jumps from H' + headings[index - 1].level + ' "' + headings[index - 1].text
                + '" to H' + headings[index].level + ' "' + headings[index].text + '".',
            );
        }
    }

    const imagesMissingAlt = await page.locator('img:not([alt])').evaluateAll((nodes) => nodes
        .filter((node) => isVisible(node))
        .map((node) => node.outerHTML.slice(0, 220)));
    if (imagesMissingAlt.length > 0) {
        throw new Error(prefix + ' visible images without alt: ' + imagesMissingAlt.join(' | '));
    }

    const unnamedControls = await page.locator('a[href],button,summary,input:not([type="hidden"]),select,textarea').evaluateAll((nodes) => nodes
        .filter((node) => isVisible(node))
        .filter((node) => !accessibleName(node))
        .map((node) => node.outerHTML.slice(0, 220)));
    if (unnamedControls.length > 0) {
        throw new Error(prefix + ' visible interactive elements without accessible names: ' + unnamedControls.join(' | '));
    }

    const unlabeledFields = await page.locator('input:not([type="hidden"]),select,textarea').evaluateAll((nodes) => nodes
        .filter((node) => isVisible(node))
        .filter((node) => {
            const labels = 'labels' in node ? Array.from(node.labels ?? []) : [];
            return !node.getAttribute('aria-label')
                && !node.getAttribute('aria-labelledby')
                && labels.every((label) => !(label.textContent ?? '').trim());
        })
        .map((node) => node.outerHTML.slice(0, 220)));
    if (unlabeledFields.length > 0) {
        throw new Error(prefix + ' visible form controls without labels: ' + unlabeledFields.join(' | '));
    }

    const invalidWithoutErrorAssociation = await page.locator('[aria-invalid="true"]').evaluateAll((nodes) => nodes
        .filter((node) => isVisible(node))
        .filter((node) => !node.getAttribute('aria-describedby') && !node.getAttribute('aria-errormessage'))
        .map((node) => node.outerHTML.slice(0, 220)));
    if (invalidWithoutErrorAssociation.length > 0) {
        throw new Error(prefix + ' invalid controls without error association: ' + invalidWithoutErrorAssociation.join(' | '));
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

        return {
            skip: active.hasAttribute('data-public-skip-link'),
            visible: rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none',
            indicator: hasVisibleFocusIndicator(style),
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

            return {
                indicator: hasVisibleFocusIndicator(style),
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
            .filter((node) => isVisible(node))
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

function isVisible(node) {
    const style = getComputedStyle(node);
    const rect = node.getBoundingClientRect();

    return style.display !== 'none'
        && style.visibility !== 'hidden'
        && Number.parseFloat(style.opacity || '1') > 0
        && rect.width > 0
        && rect.height > 0;
}

function accessibleName(node) {
    const ariaLabel = (node.getAttribute('aria-label') ?? '').trim();
    if (ariaLabel) return ariaLabel;

    const labelledBy = (node.getAttribute('aria-labelledby') ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .map((id) => document.getElementById(id)?.textContent?.trim() ?? '')
        .filter(Boolean)
        .join(' ');
    if (labelledBy) return labelledBy;

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
}

function hasVisibleFocusIndicator(style) {
    const outlineWidth = Number.parseFloat(style.outlineWidth || '0');
    const outlineVisible = outlineWidth >= 1
        && style.outlineStyle !== 'none'
        && style.outlineColor !== 'transparent'
        && style.outlineColor !== 'rgba(0, 0, 0, 0)';

    return outlineVisible || (style.boxShadow && style.boxShadow !== 'none');
}

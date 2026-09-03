import fs from "node:fs/promises";
import path from "node:path";
import { chromium } from "playwright";

const outputDir = path.resolve("output", "playwright");
const outputPath = path.join(outputDir, "mbank-header-metrics.json");

async function measure(page, url, kind) {
    await page.goto(url, { waitUntil: "domcontentloaded", timeout: 120000 });
    await page.setViewportSize({ width: 1440, height: 1200 });
    await page.waitForTimeout(2500);

    if (kind === "mbank") {
        await page.waitForSelector('[data-test-id="MegaMenuLevelOne:Container"]', {
            timeout: 30000,
        });
    } else {
        await page.waitForSelector("nav.sticky", { timeout: 30000 });
    }

    return await page.evaluate((pageKind) => {
        const px = (value) => {
            const parsed = Number.parseFloat(value ?? "0");
            return Number.isFinite(parsed) ? parsed : 0;
        };

        const rectMetrics = (element) => {
            if (!element) {
                return null;
            }

            const rect = element.getBoundingClientRect();
            const styles = window.getComputedStyle(element);

            return {
                width: Number(rect.width.toFixed(2)),
                height: Number(rect.height.toFixed(2)),
                className:
                    typeof element.className === "string"
                        ? element.className
                        : (element.getAttribute("class") ?? null),
                paddingTop: px(styles.paddingTop),
                paddingRight: px(styles.paddingRight),
                paddingBottom: px(styles.paddingBottom),
                paddingLeft: px(styles.paddingLeft),
                marginTop: px(styles.marginTop),
                marginRight: px(styles.marginRight),
                marginBottom: px(styles.marginBottom),
                marginLeft: px(styles.marginLeft),
                fontSize: px(styles.fontSize),
                lineHeight: px(styles.lineHeight),
                fontWeight: styles.fontWeight,
                color: styles.color,
                backgroundColor: styles.backgroundColor,
                borderColor: styles.borderColor,
                borderRadius: styles.borderRadius,
            };
        };

        const sequenceMetrics = (elements) => {
            const items = elements
                .filter(Boolean)
                .map((element) => {
                    const rect = element.getBoundingClientRect();
                    const styles = window.getComputedStyle(element);

                    return {
                        text:
                            element.textContent
                                ?.replace(/\s+/g, " ")
                                .trim() ?? "",
                        left: Number(rect.left.toFixed(2)),
                        right: Number(rect.right.toFixed(2)),
                        width: Number(rect.width.toFixed(2)),
                        height: Number(rect.height.toFixed(2)),
                        className:
                            typeof element.className === "string"
                                ? element.className
                                : (element.getAttribute("class") ?? null),
                        fontSize: px(styles.fontSize),
                        lineHeight: px(styles.lineHeight),
                        fontWeight: styles.fontWeight,
                        paddingLeft: px(styles.paddingLeft),
                        paddingRight: px(styles.paddingRight),
                    };
                });

            const gaps = [];

            for (let index = 1; index < items.length; index += 1) {
                gaps.push(
                    Number((items[index].left - items[index - 1].right).toFixed(2)),
                );
            }

            return {
                items,
                gaps,
            };
        };

        const iconMetrics = (element) => {
            if (!element) {
                return null;
            }

            const rect = element.getBoundingClientRect();
            const styles = window.getComputedStyle(element);
            const path = element.querySelector("path");

            return {
                width: Number(rect.width.toFixed(2)),
                height: Number(rect.height.toFixed(2)),
                color: styles.color,
                stroke: path?.getAttribute("stroke") ?? null,
                strokeWidth: path?.getAttribute("stroke-width") ?? null,
            };
        };

        if (pageKind === "mbank") {
            const levelOne = document.querySelector(
                '[data-test-id="MegaMenuLevelOne:Container"]',
            );
            const levelTwo = document.querySelector("#MegaMenuLevelTwo");
            const topShell = document.querySelector("#mainMenu");
            const mainShell = document.querySelector("#MegaMenuLevelTwo nav");
            const topNavLink = document.querySelector(
                '[data-test-id="MegaMenuSegment:Link"]',
            );
            const topNavLinks = Array.from(
                document.querySelectorAll('[data-test-id="MegaMenuSegment:Link"]'),
            );
            const mainNavButton = document.querySelector(
                '[data-test-id^="MegaMenuLevelTwo:SectionTitle:"]',
            );
            const utilityLink = document.querySelector(
                '[data-test-id="MegaMenuCustomLink:Item:Link"]',
            );
            const utilityLinks = Array.from(
                document.querySelectorAll(
                    '[data-test-id="MegaMenuCustomLink:Item:Link"]',
                ),
            );
            const primaryButton = document.querySelector(
                '[data-test-id="MegaMenuPrimaryButton:Button"]',
            );
            const topChevron = topNavLink?.querySelector("svg");
            const mainSearchButton = document.querySelector(
                '[data-test-id="MegaMenu:MegaMenuSearchBar:MegaMenuSearchBarIcon:Open"]',
            );
            const mainSearchIcon = mainSearchButton?.querySelector("svg");

            return {
                rootFontSize: px(
                    window.getComputedStyle(document.documentElement).fontSize,
                ),
                topBar: rectMetrics(levelOne),
                mainBar: rectMetrics(levelTwo),
                topShell: rectMetrics(topShell),
                mainShell: rectMetrics(mainShell),
                topNavLink: rectMetrics(topNavLink),
                topNavLinks: sequenceMetrics(topNavLinks),
                mainNavButton: rectMetrics(mainNavButton),
                utilityLink: rectMetrics(utilityLink),
                utilityLinks: sequenceMetrics(utilityLinks),
                primaryButton: rectMetrics(primaryButton),
                topChevron: iconMetrics(topChevron),
                mainSearchButton: rectMetrics(mainSearchButton),
                mainSearchIcon: iconMetrics(mainSearchIcon),
            };
        }

        const rootNav = document.querySelector("nav.sticky");
        const topBar = rootNav?.children?.[0] ?? null;
        const mainBar = rootNav?.children?.[1] ?? null;
        const topShell =
            rootNav?.querySelector(":scope > div:first-child > div") ?? null;
        const mainShell =
            rootNav?.querySelector(":scope > div:nth-child(2)") ?? null;
        const topSegmentContainer =
            rootNav?.querySelector(
                ":scope > div:first-child > div > div > div:first-child",
            ) ?? null;
        const utilityContainer =
            rootNav?.querySelector(
                ":scope > div:first-child > div > div > div:nth-child(2)",
            ) ?? null;
        const topNavLinks = Array.from(
            topSegmentContainer?.querySelectorAll("a") ?? [],
        );
        const topNavLink = rootNav?.querySelector(
            'div:first-child a[href="/nauka"], div:first-child a[href="/login"]',
        );
        const mainNavLink = rootNav?.querySelector(
            'div:nth-child(2) a[href="/testy"]',
        );
        const utilityLinks = Array.from(utilityContainer?.querySelectorAll("a") ?? []);
        const utilityLink = rootNav?.querySelector(
            'div:first-child a[href="/najtrudniejsze-pytania-na-prawo-jazdy"]',
        );
        const primaryButton =
            rootNav?.querySelector('div:first-child a[href="/register"]') ??
            rootNav?.querySelector('div:first-child button[aria-haspopup="menu"]');
        const mainChevronButton = rootNav?.querySelector(
            'div:nth-child(2) button[aria-label^="Rozwiń sekcję"]',
        );
        const mainChevronIcon = mainChevronButton?.querySelector("svg");
        const categoryButton = rootNav?.querySelector(
            '#header-category-listbox',
        )?.previousElementSibling ??
            rootNav?.querySelector('button[aria-controls="header-category-listbox"]');

        return {
            rootFontSize: px(
                window.getComputedStyle(document.documentElement).fontSize,
            ),
            topBar: rectMetrics(topBar),
            mainBar: rectMetrics(mainBar),
            topShell: rectMetrics(topShell),
            mainShell: rectMetrics(mainShell),
            topNavLink: rectMetrics(topNavLink),
            topNavLinks: sequenceMetrics(topNavLinks),
            mainNavButton: rectMetrics(mainNavLink),
            utilityLink: rectMetrics(utilityLink),
            utilityLinks: sequenceMetrics(utilityLinks),
            primaryButton: rectMetrics(primaryButton),
            topChevron: null,
            mainSearchButton: rectMetrics(categoryButton),
            mainSearchIcon: iconMetrics(mainChevronIcon),
        };
    }, kind);
}

const browser = await chromium.launch({ headless: true });

try {
    const mbankPage = await browser.newPage();
    const localPage = await browser.newPage();

    const [mbank, local] = await Promise.all([
        measure(mbankPage, "https://www.mbank.pl/indywidualny/", "mbank"),
        measure(localPage, "http://localhost:8000/", "local"),
    ]);

    const report = {
        measuredAt: new Date().toISOString(),
        viewport: { width: 1440, height: 1200 },
        mbank,
        local,
    };

    await fs.mkdir(outputDir, { recursive: true });
    await fs.writeFile(outputPath, JSON.stringify(report, null, 2));

    console.log(JSON.stringify(report, null, 2));
} finally {
    await browser.close();
}

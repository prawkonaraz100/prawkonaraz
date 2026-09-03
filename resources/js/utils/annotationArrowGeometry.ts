export interface ArrowAnnotationLike {
    x_percent: number | null | undefined;
    y_percent: number | null | undefined;
    arrow_length_percent: number | null | undefined;
    arrow_angle_degrees: number | null | undefined;
    arrow_stroke_percent: number | null | undefined;
    arrow_head_percent: number | null | undefined;
}

export interface ArrowPercentGeometry {
    startX: number;
    startY: number;
    endX: number;
    endY: number;
    strokeWidth: number;
    headPoints: string;
}

const ARROW_DEFAULT_LENGTH_PERCENT = 18;
const ARROW_DEFAULT_ANGLE_DEGREES = 45;
const ARROW_DEFAULT_STROKE_PERCENT = 1.6;
const ARROW_DEFAULT_HEAD_PERCENT = 3.5;

const clamp = (value: number, min: number, max: number): number =>
    Math.min(Math.max(value, min), max);

const normalizePercent = (value: number | null | undefined, fallback: number): number => {
    if (value === null || value === undefined) {
        return fallback;
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return fallback;
    }

    return clamp(numeric, 0, 100);
};

const normalizeLength = (value: number | null | undefined): number => {
    if (value === null || value === undefined) {
        return ARROW_DEFAULT_LENGTH_PERCENT;
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return ARROW_DEFAULT_LENGTH_PERCENT;
    }

    return clamp(numeric, 1, 100);
};

const normalizeStroke = (value: number | null | undefined): number => {
    if (value === null || value === undefined) {
        return ARROW_DEFAULT_STROKE_PERCENT;
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return ARROW_DEFAULT_STROKE_PERCENT;
    }

    return clamp(numeric, 0.5, 8);
};

const normalizeHead = (value: number | null | undefined): number => {
    if (value === null || value === undefined) {
        return ARROW_DEFAULT_HEAD_PERCENT;
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return ARROW_DEFAULT_HEAD_PERCENT;
    }

    return clamp(numeric, 2, 30);
};

const normalizeAngle = (value: number | null | undefined): number => {
    if (value === null || value === undefined) {
        return ARROW_DEFAULT_ANGLE_DEGREES;
    }

    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return ARROW_DEFAULT_ANGLE_DEGREES;
    }

    const normalized = numeric % 360;

    return normalized >= 0 ? normalized : normalized + 360;
};

export const computeArrowPercentGeometry = (
    annotation: ArrowAnnotationLike,
): ArrowPercentGeometry => {
    const startX = normalizePercent(annotation.x_percent, 50);
    const startY = normalizePercent(annotation.y_percent, 50);
    const length = normalizeLength(annotation.arrow_length_percent);
    const angle = normalizeAngle(annotation.arrow_angle_degrees);
    const radians = (angle * Math.PI) / 180;
    const strokeWidth = normalizeStroke(annotation.arrow_stroke_percent);
    const headLength = normalizeHead(annotation.arrow_head_percent);

    const endX = normalizePercent(startX + (Math.cos(radians) * length), startX);
    const endY = normalizePercent(startY + (Math.sin(radians) * length), startY);
    const baseX = endX - (Math.cos(radians) * headLength);
    const baseY = endY - (Math.sin(radians) * headLength);
    const wing = headLength * 0.58;
    const perpX = -Math.sin(radians);
    const perpY = Math.cos(radians);

    const leftX = normalizePercent(baseX + (perpX * wing), endX);
    const leftY = normalizePercent(baseY + (perpY * wing), endY);
    const rightX = normalizePercent(baseX - (perpX * wing), endX);
    const rightY = normalizePercent(baseY - (perpY * wing), endY);

    return {
        startX,
        startY,
        endX,
        endY,
        strokeWidth,
        headPoints: `${endX},${endY} ${leftX},${leftY} ${rightX},${rightY}`,
    };
};

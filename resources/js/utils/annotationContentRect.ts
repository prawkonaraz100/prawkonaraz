export interface AnnotationContentRectInput {
    containerWidth: number;
    containerHeight: number;
    naturalWidth: number;
    naturalHeight: number;
    objectPosition?: string | null;
}

export interface AnnotationContentRect {
    left: number;
    top: number;
    width: number;
    height: number;
}

const clamp = (value: number, min: number, max: number) =>
    Math.min(Math.max(value, min), max);

const normalizeObjectPositionTokens = (value: string | null | undefined): [string, string] => {
    if (typeof value !== 'string' || value.trim() === '') {
        return ['50%', '50%'];
    }

    const tokens = value.trim().split(/\s+/u).filter(Boolean);

    if (tokens.length === 0) {
        return ['50%', '50%'];
    }

    if (tokens.length === 1) {
        const token = tokens[0].toLowerCase();

        if (token === 'top' || token === 'bottom') {
            return ['50%', token];
        }

        if (token === 'left' || token === 'right') {
            return [token, '50%'];
        }

        return [tokens[0], '50%'];
    }

    return [tokens[0], tokens[1]];
};

const keywordToRatio = (
    token: string,
    axis: 'x' | 'y',
): number | null => {
    const normalized = token.toLowerCase();

    if (axis === 'x') {
        if (normalized === 'left') {
            return 0;
        }

        if (normalized === 'center') {
            return 0.5;
        }

        if (normalized === 'right') {
            return 1;
        }

        return null;
    }

    if (normalized === 'top') {
        return 0;
    }

    if (normalized === 'center') {
        return 0.5;
    }

    if (normalized === 'bottom') {
        return 1;
    }

    return null;
};

const tokenToRatio = (
    token: string,
    axis: 'x' | 'y',
    availableSpace: number,
): number => {
    const keyword = keywordToRatio(token, axis);

    if (keyword !== null) {
        return keyword;
    }

    if (token.endsWith('%')) {
        const parsed = Number.parseFloat(token.slice(0, -1));

        if (Number.isFinite(parsed)) {
            return clamp(parsed / 100, 0, 1);
        }
    }

    if (token.endsWith('px')) {
        const parsed = Number.parseFloat(token.slice(0, -2));

        if (Number.isFinite(parsed) && availableSpace > 0) {
            return clamp(parsed / availableSpace, 0, 1);
        }
    }

    return 0.5;
};

export const computeContainContentRect = ({
    containerWidth,
    containerHeight,
    naturalWidth,
    naturalHeight,
    objectPosition,
}: AnnotationContentRectInput): AnnotationContentRect => {
    if (
        !Number.isFinite(containerWidth)
        || !Number.isFinite(containerHeight)
        || !Number.isFinite(naturalWidth)
        || !Number.isFinite(naturalHeight)
        || containerWidth <= 0
        || containerHeight <= 0
        || naturalWidth <= 0
        || naturalHeight <= 0
    ) {
        return {
            left: 0,
            top: 0,
            width: 0,
            height: 0,
        };
    }

    const scale = Math.min(containerWidth / naturalWidth, containerHeight / naturalHeight);
    const width = naturalWidth * scale;
    const height = naturalHeight * scale;
    const freeX = Math.max(containerWidth - width, 0);
    const freeY = Math.max(containerHeight - height, 0);
    const [xToken, yToken] = normalizeObjectPositionTokens(objectPosition);

    return {
        left: freeX * tokenToRatio(xToken, 'x', freeX),
        top: freeY * tokenToRatio(yToken, 'y', freeY),
        width,
        height,
    };
};

// AUTO-GENERATED RECONSTRUCTION CONTRACT.
// Coordinate system: 720 × 1280. Do not redesign classic-quiz-v1.
// Scale every numeric layout value by outputWidth / 720.

export const CLASSIC_QUIZ_V1 = {
  version: 'classic-quiz-v1',
  width: 720,
  height: 1280,
  fps: 24,
  totalFrames: 528,

  colors: {
    surfaceNavy: '#0A111F',
    buttonNavy: '#1D2739',
    pillNavy: '#101930',
    buttonBorder: '#63748A',
    brandYellow: '#F8CB14',
    dangerRed: '#ED4341',
    successGreen: '#21C45D',
    white: '#FFFFFF',
    mutedText: '#CBD4E1',
    progressTrack: '#313F54',
  },

  font: {
    family: 'Lato',
    primaryWeight: 900,
    countdownWeight: 800,
    fallback: '"Arial Black", Arial, sans-serif',
  },

  timeline: {
    intro: [0, 96],
    question: [96, 217],
    countdown3: [217, 237],
    countdown2: [237, 257],
    countdown1: [257, 277],
    countdownExit: [277, 281],
    answer: [281, 340],
    explanation: [340, 449],
    comparison: [449, 528],
  },

  global: {
    logo: {x: 30, y: 27, width: 198, height: 25},
    progress: {x: 28, y: 1257, width: 665, height: 8, radius: 4},
    background: {
      objectPosition: '68% 50%',
      blur: 40,
      brightness: 0.60,
      overscanScale: 1.08,
    },
  },

  intro: {
    panel: {x: 35, y: 310, width: 651, height: 631, radius: 36},
    badge: {x: 66, y: 350, width: 190, height: 38, radius: 19},
    badgeFontSize: 24,
    headlineFontSize: 72,
    promptFontSize: 58,
    circle: {cx: 360, cy: 802, radius: 96, strokeWidth: 4},
    circleNumberFontSize: 86,
  },

  question: {
    media: {x: 28, y: 112, width: 664, height: 377, radius: 26, borderWidth: 3},
    pill: {x: 42, y: 510, width: 252, height: 38, radius: 19},
    pillFontSize: 22,
    text: {x: 56, y: 577, width: 608, height: 132, fontSize: 38, lineHeight: 41, minFontSize: 30, maxLines: 3},
    leftButton: {x: 55, y: 846, width: 281, height: 120, radius: 28, borderWidth: 3},
    rightButton: {x: 385, y: 846, width: 281, height: 120, radius: 28, borderWidth: 3},
    buttonFontSize: 48,
  },

  countdown: {
    outer: {x: 277, y: 932, width: 167, height: 167},
    inner: {x: 282, y: 938, width: 156, height: 156},
    fontSize: 94,
  },

  answer: {
    media: {x: 28, y: 107, width: 664, height: 377, radius: 26, borderWidth: 3},
    card: {x: 56, y: 690, width: 610, height: 300, radius: 36},
    labelFontSize: 30,
    answerFontSize: 111,
  },

  explanation: {
    panel: {x: 35, y: 115, width: 651, height: 996, radius: 36},
    assetCard: {x: 180, y: 160, width: 360, height: 420, radius: 28},
    assetBox: {x: 200, y: 210, width: 320, height: 320},
    badge: {x: 66, y: 636, width: 122, height: 48, radius: 18},
    badgeFontSize: 38,
    titleFontSize: 42,
    keyFontSize: 64,
    noteFontSize: 34,
  },

  comparison: {
    panel: {x: 30, y: 105, width: 661, height: 1026, radius: 34},
    titleFontSize: 38,
    leftAssetCard: {x: 55, y: 180, width: 260, height: 310, radius: 20},
    rightAssetCard: {x: 405, y: 180, width: 260, height: 310, radius: 20},
    leftBadge: {x: 120, y: 526, width: 94, height: 44, radius: 18},
    rightBadge: {x: 460, y: 526, width: 114, height: 44, radius: 18},
    badgeFontSize: 32,
    labelFontSize: 32,
    memoryCard: {x: 56, y: 746, width: 610, height: 244, radius: 30},
    memoryMainFontSize: 54,
    memorySubFontSize: 34,
  },
} as const;

export const scaleClassicQuizV1 = (outputWidth: number) => outputWidth / 720;

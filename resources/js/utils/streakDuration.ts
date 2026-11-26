export const formatStreakDuration = (seconds: number | null | undefined): string => {
    if (seconds == null || !Number.isFinite(seconds) || seconds < 0) return '—';
    const total = Math.floor(seconds);
    const hours = Math.floor(total / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const rest = String(total % 60).padStart(2, '0');
    return hours > 0 ? `${hours}:${String(minutes).padStart(2, '0')}:${rest}` : `${String(minutes).padStart(2, '0')}:${rest}`;
};

export interface StreakLeader {
    position: number;
    name: string;
    score: number;
    duration_seconds?: number | null;
    avatar_url: string | null;
    initials: string;
    is_me: boolean;
}

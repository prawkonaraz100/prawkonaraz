export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_admin: boolean;
    avatar_url?: string | null;
    avatar_initials?: string;
    has_uploaded_avatar?: boolean;
}

export interface AppMeta {
    name: string;
    request_id?: string | null;
}

export interface NavigationLink {
    label: string;
    href: string;
    match: string[];
    variant?: 'text' | 'primary';
    icon?: 'baza-pytan' | 'plany-nauki' | 'kursy' | 'cennik' | 'kontakt';
}

export interface NavigationGroup {
    title: string;
    links: NavigationLink[];
}

export interface NavigationData {
    top: NavigationLink[];
    utility: NavigationLink[];
    primary: NavigationLink[];
    header_actions: NavigationLink[];
    footer_groups: NavigationGroup[];
    learning_href: string;
    logout_href: string | null;
}

export interface FooterBrandData {
    aria_label: string;
    name: string;
    tagline: string;
}

export interface FooterActionData {
    label: string;
    href: string;
}

export interface FooterLinkData {
    label: string;
    href: string;
    icon?: 'facebook' | 'youtube' | 'instagram' | 'tiktok';
}

export interface FooterMobileAppData {
    label: string;
    icon: 'apple' | 'google-play';
    status: string;
}

export interface FooterData {
    home_href: string;
    brand: FooterBrandData;
    description: string;
    primary_action: FooterActionData;
    secondary_action: FooterActionData;
    legal_links: FooterLinkData[];
    service_links: FooterLinkData[];
    social_links: FooterLinkData[];
    mobile_apps: FooterMobileAppData[];
    language: string;
    groups: NavigationGroup[];
    copyright: string;
}

export interface StudyContextCategory {
    id: number;
    code: string;
    name: string;
    short_name: string;
}

export interface AuthDrawersData {
    registrationCategories: StudyContextCategory[];
    enabledSocialProviders: Array<'google' | 'facebook'>;
    googleIdentityRegistration: GoogleIdentityRegistrationData | null;
    paymentRequired: boolean;
}

export interface GoogleIdentityData {
    enabled: boolean;
    oneTapEnabled: boolean;
    clientId: string | null;
    loginUrl: string;
}

export interface GoogleIdentityRegistrationData {
    pending: boolean;
    email: string;
    name: string | null;
    avatarUrl: string | null;
    expiresAt: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    app: AppMeta;
    auth: {
        user: User | null;
    };
    authDrawers: AuthDrawersData;
    googleIdentity: GoogleIdentityData;
    navigation: NavigationData;
    footer: FooterData;
    studyContext: {
        targetCategoryId: number | null;
        visualExplanationsEnabled: boolean;
        visualExplanationsMode: 'before_answer' | 'after_incorrect' | 'off';
        canSwitchCategory: boolean;
        categoryLocked: boolean;
        categories: StudyContextCategory[];
    };
};

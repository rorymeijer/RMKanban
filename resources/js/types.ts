export interface AuthUser {
    id: number;
    name: string;
    username: string;
    email: string;
    is_admin: boolean;
    locale: string;
}

export interface SharedProps {
    app: {
        name: string;
        version: string;
        locale: string;
    };
    auth: {
        user: AuthUser | null;
    };
    flash: {
        status: string | null;
        mail_test: string | null;
    };
    license: {
        features: string[];
        package: string;
    };
    [key: string]: unknown;
}

export interface Label {
    id: number;
    name: string | null;
    color: string;
}

export interface Card {
    id: number;
    title: string;
    position: string;
    cover_color: string | null;
    start_date?: string | null;
    due_date: string | null;
    labels: Label[];
}

export interface BoardList {
    id: number;
    name: string;
    position: string;
    wip_limit: number | null;
    collapsed: boolean;
    cards: Card[];
}

export interface BoardSummary {
    id: number;
    name: string;
    slug: string;
    color: string | null;
}

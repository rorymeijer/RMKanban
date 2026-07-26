import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { I18nProvider } from '@/lib/i18n';
import type { SharedProps } from '@/types';

let appName = 'Board';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { staleTime: 30_000, refetchOnWindowFocus: false },
    },
});

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: false });
        const page = pages[`./pages/${name}.tsx`];
        if (!page) {
            throw new Error(`Onbekende pagina: ${name}`);
        }
        return page();
    },
    setup({ el, App, props }) {
        const shared = props.initialPage.props as unknown as SharedProps;
        appName = shared.app?.name ?? appName;
        const locale = shared.app?.locale ?? 'nl';
        createRoot(el).render(
            <QueryClientProvider client={queryClient}>
                <I18nProvider locale={locale}>
                    <App {...props} />
                </I18nProvider>
            </QueryClientProvider>,
        );
    },
    progress: {
        color: 'hsl(243 75% 59%)',
        showSpinner: false,
    },
});

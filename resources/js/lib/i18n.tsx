import { createContext, useCallback, useContext, useMemo, type ReactNode } from 'react';
import { nl } from '@/locales/nl';
import { en } from '@/locales/en';

type Messages = Record<string, string>;

const catalogs: Record<string, Messages> = { nl, en };

type Translate = (key: string, params?: Record<string, string | number>) => string;

const I18nContext = createContext<{ locale: string; t: Translate }>({
    locale: 'nl',
    t: (key) => key,
});

export function I18nProvider({ locale, children }: { locale: string; children: ReactNode }) {
    const messages = catalogs[locale] ?? catalogs.nl;

    const t = useCallback<Translate>(
        (key, params) => {
            let text = messages[key] ?? catalogs.nl[key] ?? key;
            if (params) {
                for (const [name, value] of Object.entries(params)) {
                    text = text.replace(new RegExp(`:${name}\\b`, 'g'), String(value));
                }
            }
            return text;
        },
        [messages],
    );

    const value = useMemo(() => ({ locale, t }), [locale, t]);

    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export function useTranslation() {
    return useContext(I18nContext);
}

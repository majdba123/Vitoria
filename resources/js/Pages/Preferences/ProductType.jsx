import { Sprout, Stethoscope, ChevronRight } from 'lucide-react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useI18n } from '@/hooks/use-i18n';

const TYPE_META = {
    agriculture: { icon: Sprout, labelKey: 'agriculture_label', descKey: 'agriculture_description', buttonKey: 'agriculture_button' },
    veterinary: { icon: Stethoscope, labelKey: 'veterinary_label', descKey: 'veterinary_description', buttonKey: 'veterinary_button' },
};

export default function ProductTypePreference({ selectedType }) {
    const { preferences } = useI18n();

    return (
        <PublicLayout title={preferences.title} noindex>
            <div className="page-shell min-h-[calc(100vh-5rem)] py-10 sm:py-16">
                <div className="mx-auto max-w-6xl border-y-2 border-foreground py-8 sm:py-10">
                    <div className="max-w-2xl text-start">
                        <span className="eyebrow">{preferences.start_browsing}</span>
                        <h1 className="mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl">{preferences.heading}</h1>
                        <p className="mt-3 text-sm leading-7 text-muted-foreground">{preferences.description}</p>
                    </div>

                    <div className="home-type-grid mt-10">
                        {Object.entries(TYPE_META).map(([value, meta]) => {
                            const isSelected = selectedType === value;
                            const Icon = meta.icon;
                            return (
                                <a
                                    key={value}
                                    href={route('product-type.select', { preferred_product_type: value, redirect_to: 'categories' })}
                                    className={`home-type-card ${isSelected ? 'is-selected' : ''}`}
                                    aria-label={preferences[meta.buttonKey]}
                                    aria-current={isSelected ? 'true' : undefined}
                                >
                                    <span className="flex items-start justify-between gap-4">
                                        <span className="icon-chip flex h-12 w-12 text-xl">
                                            <Icon className="size-6" />
                                        </span>
                                        <span className={`badge ${isSelected ? 'badge-brand' : ''}`}>
                                            {isSelected ? preferences.currently_selected : preferences.choose}
                                        </span>
                                    </span>

                                    <span className="mt-6 block">
                                        <span className="block text-2xl font-bold text-foreground">{preferences[meta.labelKey]}</span>
                                        <span className="mt-3 block text-sm leading-7 text-muted-foreground">{preferences[meta.descKey]}</span>
                                    </span>

                                    <span className="mt-6 block space-y-3 text-sm text-muted-foreground">
                                        <span className="flex items-center gap-2">
                                            <span className="size-2 rounded-full bg-primary" />
                                            <span>{preferences.matching_categories_only}</span>
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span className="size-2 rounded-full bg-primary" />
                                            <span>{preferences.filter_by_selected_section}</span>
                                        </span>
                                    </span>

                                    <span className="home-type-action mt-8">
                                        {preferences[meta.buttonKey]}
                                        <ChevronRight className="h-4 w-4 rtl:-scale-x-100" />
                                    </span>
                                </a>
                            );
                        })}
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}

import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { Field, FieldLabel } from '@/components/ui/field';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { opcache } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Monitor OPCache', href: opcache().url },
];

export default function Dashboard({
    data,
    includeScripts,
}: {
    data: object;
    includeScripts: boolean;
}) {
    function toggleScripts(checked: boolean) {
        router.get(
            opcache().url,
            { include_scripts: checked ? '1' : '0' },
            {
                preserveScroll: true,
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Monitor OPCache" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Field orientation="horizontal" className="w-fit">
                    <Switch
                        id="switch-include-scripts"
                        defaultChecked={includeScripts}
                        onCheckedChange={toggleScripts}
                    />
                    <FieldLabel htmlFor="switch-include-scripts">
                        Include cached scripts?
                    </FieldLabel>
                </Field>

                <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <pre className="font-mono text-sm break-all whitespace-pre-wrap">
                        {JSON.stringify(data, null, 4)}
                    </pre>
                </div>
            </div>
        </AppLayout>
    );
}

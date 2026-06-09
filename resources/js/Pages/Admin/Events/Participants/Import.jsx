import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/react';

function formatImportErrors(errors) {
    return Object.entries(errors)
        .filter(([key]) => key.startsWith('rows.') || key === 'file')
        .map(([key, messages]) => {
            const label =
                key === 'file'
                    ? 'File'
                    : key.replace(/^rows\./, 'Row ').replace(/\./g, ' — ');

            return {
                key,
                label,
                messages: Array.isArray(messages) ? messages : [messages],
            };
        });
}

export default function Import({
    event,
    participantUnitLabel,
    participantTypes,
    statuses,
}) {
    const { data, setData, post, processing, errors } = useForm({
        file: null,
    });

    const rowErrors = formatImportErrors(errors);

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.participants.import.store', event.id), {
            forceFormData: true,
        });
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: participantUnitLabel,
                    href: route('admin.events.participants.index', event.id),
                },
                { label: 'Import CSV' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold">
                        Import {participantUnitLabel} from CSV
                    </h2>
                    <Button
                        variant="outline"
                        render={
                            <Link
                                href={route('admin.events.participants.index', event.id)}
                            />
                        }
                    >
                        Back to list
                    </Button>
                </div>
            }
        >
            <Head title={`Import ${participantUnitLabel} — ${event.name}`} />

            <div className="mx-auto max-w-2xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>CSV format</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm text-muted-foreground">
                        <p>
                            Upload a CSV file with one {participantUnitLabel.toLowerCase()} per
                            row. Required columns: <strong>type</strong>,{' '}
                            <strong>name</strong>. Optional: <strong>code</strong>,{' '}
                            <strong>branch_id</strong>, <strong>status</strong> (defaults to
                            active).
                        </p>
                        <pre className="overflow-x-auto rounded-lg border bg-muted/40 p-4 text-xs text-foreground">
                            {`type,name,code,branch_id,status
state,Selangor,SGR,,active
state,Johor,JHR,,active`}
                        </pre>
                        <div className="space-y-1">
                            <p>
                                <strong>type:</strong> {participantTypes.join(', ')}
                            </p>
                            <p>
                                <strong>status:</strong> {statuses.join(', ')}
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            render={
                                <a
                                    href={route(
                                        'admin.events.participants.import.template',
                                        event.id,
                                    )}
                                />
                            }
                        >
                            Download template
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Upload file</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="file">CSV file</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    onChange={(e) =>
                                        setData('file', e.target.files[0] ?? null)
                                    }
                                />
                                <InputError message={errors.file} />
                            </div>

                            {rowErrors.length > 0 && (
                                <div className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm">
                                    <p className="mb-2 font-medium text-destructive">
                                        Fix the following issues and try again:
                                    </p>
                                    <ul className="space-y-1 text-destructive">
                                        {rowErrors.map((item) =>
                                            item.messages.map((message) => (
                                                <li key={`${item.key}-${message}`}>
                                                    <span className="font-medium">
                                                        {item.label}:
                                                    </span>{' '}
                                                    {message}
                                                </li>
                                            )),
                                        )}
                                    </ul>
                                </div>
                            )}

                            <div className="flex gap-4">
                                <Button
                                    type="submit"
                                    disabled={processing || !data.file}
                                >
                                    Import participants
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.participants.index',
                                                event.id,
                                            )}
                                        />
                                    }
                                >
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
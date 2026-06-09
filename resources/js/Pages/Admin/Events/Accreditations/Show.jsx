import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';

export default function Show({ event, accreditation, qrSvg }) {
    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Accreditations', href: route('admin.events.accreditations.index', event.id) },
                { label: accreditation.id },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold">Accreditation #{accreditation.id}</h2>
                    <Button variant="outline" render={<Link href={route('admin.events.accreditations.index', event.id)} />}>
                        Back
                    </Button>
                </div>
            }
        >
            <Head title={`Accreditation #${accreditation.id} — ${event.name}`} />

            <div className="mx-auto max-w-4xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <p className="text-sm text-muted-foreground">Type</p>
                            <Badge>{accreditation.type}</Badge>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Status</p>
                            <Badge>{accreditation.status}</Badge>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Accredited To</p>
                            <p>{accreditation.accreditable?.name || accreditation.eventParticipant?.name}</p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">QR Data</p>
                            <code className="text-xs break-all">{accreditation.qr_code}</code>
                        </div>
                        {qrSvg && (
                            <div>
                                <p className="text-sm text-muted-foreground">QR Code</p>
                                <div dangerouslySetInnerHTML={{ __html: qrSvg }} className="border p-2 bg-white inline-block" />
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex gap-2">
                    <Button variant="outline" render={<Link href={route('admin.events.accreditations.edit', [event.id, accreditation.id])} />}>
                        Edit
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() => {
                            if (confirm('Revoke this accreditation?')) {
                                router.delete(route('admin.events.accreditations.destroy', [event.id, accreditation.id]));
                            }
                        }}
                    >
                        Revoke
                    </Button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

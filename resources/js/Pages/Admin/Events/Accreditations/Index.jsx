import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, Link } from '@inertiajs/react';

export default function Index({ event, accreditations, participants }) {
    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Accreditations' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Accreditations</h2>
                        <p className="text-sm text-muted-foreground">{event.name}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={
                                <Link href={route('admin.events.accreditations.create', event.id)} />
                            }
                        >
                            Issue Accreditation
                        </Button>
                        <Button
                            variant="outline"
                            render={
                                <Link href={route('admin.events.show', event.id)} />
                            }
                        >
                            Back to Event
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Accreditations — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Issued Accreditations</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {accreditations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No accreditations issued yet.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Accredited</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>QR</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {accreditations.map((acc) => (
                                        <TableRow key={acc.id}>
                                            <TableCell>
                                                <Badge variant="secondary">{acc.type}</Badge>
                                            </TableCell>
                                            <TableCell>{acc.accreditable?.name || acc.event_participant?.name}</TableCell>
                                            <TableCell>
                                                <Badge>{acc.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <code className="text-xs">{acc.qr_code}</code>
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link href={route('admin.events.accreditations.show', [event.id, acc.id])} />
                                                    }
                                                >
                                                    View
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function ActionBadge({ action }) {
    const variant =
        action === 'created'
            ? 'default'
            : action === 'deleted'
              ? 'destructive'
              : 'secondary';

    return <Badge variant={variant}>{action}</Badge>;
}

function formatChanges(values) {
    if (!values || Object.keys(values).length === 0) {
        return '—';
    }

    return Object.entries(values)
        .map(([key, value]) => `${key}: ${String(value)}`)
        .join(', ');
}

export default function Index({ logs, filters, actions, organizations }) {
    const [search, setSearch] = useState(filters.search || '');
    const [action, setAction] = useState(filters.action || '');
    const [organizationId, setOrganizationId] = useState(
        filters.organization_id || '',
    );

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.audit-logs.index'),
                {
                    search,
                    action,
                    organization_id: organizationId,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, action, organizationId]);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Audit Logs
                </h2>
            }
        >
            <Head title="Audit Logs" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>Activity Log</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-col gap-4 lg:flex-row">
                                <Input
                                    placeholder="Search actor, model, or record ID..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="lg:max-w-sm"
                                />
                                <Select
                                    value={action || 'all'}
                                    onValueChange={(value) =>
                                        setAction(value === 'all' ? '' : value)
                                    }
                                >
                                    <SelectTrigger className="w-full lg:w-40">
                                        <SelectValue placeholder="All actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All actions</SelectItem>
                                        {actions.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {item}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {organizations.length > 0 && (
                                    <Select
                                        value={organizationId || 'all'}
                                        onValueChange={(value) =>
                                            setOrganizationId(
                                                value === 'all' ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full lg:w-56">
                                            <SelectValue placeholder="All organizations" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All organizations
                                            </SelectItem>
                                            {organizations.map((organization) => (
                                                <SelectItem
                                                    key={organization.id}
                                                    value={String(organization.id)}
                                                >
                                                    {organization.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>When</TableHead>
                                        <TableHead>Action</TableHead>
                                        <TableHead>Model</TableHead>
                                        <TableHead>Actor</TableHead>
                                        <TableHead>Organization</TableHead>
                                        <TableHead>Changes</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logs.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="text-center text-muted-foreground"
                                            >
                                                No audit logs found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        logs.data.map((log) => (
                                            <TableRow key={log.id}>
                                                <TableCell className="whitespace-nowrap text-sm">
                                                    {log.created_at}
                                                </TableCell>
                                                <TableCell>
                                                    <ActionBadge action={log.action} />
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {log.auditable_type}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        ID {log.auditable_id}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {log.user ? (
                                                        <div>
                                                            <div className="font-medium">
                                                                {log.user.name}
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {log.user.email}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            System
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {log.organization?.name ?? (
                                                        <span className="text-muted-foreground">
                                                            Platform
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="max-w-xs text-sm text-muted-foreground">
                                                    {log.action === 'created' &&
                                                        formatChanges(log.new_values)}
                                                    {log.action === 'updated' && (
                                                        <span>
                                                            {formatChanges(log.old_values)} →{' '}
                                                            {formatChanges(log.new_values)}
                                                        </span>
                                                    )}
                                                    {log.action === 'deleted' &&
                                                        formatChanges(log.old_values)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {logs.links.length > 3 && (
                                <div className="flex flex-wrap gap-1">
                                    {logs.links.map((link, index) =>
                                        link.url ? (
                                            <a
                                                key={index}
                                                href={link.url}
                                                className={`inline-flex h-8 items-center rounded-lg border px-3 text-sm ${
                                                    link.active
                                                        ? 'border-primary bg-primary text-primary-foreground'
                                                        : 'border-border bg-background hover:bg-muted'
                                                }`}
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ) : (
                                            <span
                                                key={index}
                                                className="inline-flex h-8 items-center rounded-lg border border-border px-3 text-sm text-muted-foreground"
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ),
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
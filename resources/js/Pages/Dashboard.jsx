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
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Building2Icon,
    CalendarDaysIcon,
    UsersIcon,
    ZapIcon,
} from 'lucide-react';

function StatCard({ title, value, icon: Icon, description }) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
                {description && (
                    <p className="text-xs text-muted-foreground">{description}</p>
                )}
            </CardContent>
        </Card>
    );
}

function EventStatusBadge({ status }) {
    const variant =
        status === 'active'
            ? 'default'
            : status === 'published'
              ? 'secondary'
              : 'outline';

    return <Badge variant={variant}>{status}</Badge>;
}

export default function Dashboard({ stats, recentEvents, scope }) {
    const { auth } = usePage().props;
    const canViewEvents = auth.user?.can_view_events;

    const scopeLabel = scope.is_global
        ? 'All organizations'
        : scope.organization?.name ?? 'Your workspace';

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'Dashboard' }]}
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">
                            Dashboard
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Overview for {scopeLabel}
                        </p>
                    </div>
                    {canViewEvents && stats.events_count > 0 && (
                        <Button
                            variant="outline"
                            size="sm"
                            render={
                                <Link href={route('admin.events.index')} />
                            }
                        >
                            View all events
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Organizations"
                        value={stats.organizations_count}
                        icon={Building2Icon}
                        description={
                            scope.is_global
                                ? 'Tenants on the platform'
                                : 'Current tenant scope'
                        }
                    />
                    <StatCard
                        title="Events"
                        value={stats.events_count}
                        icon={CalendarDaysIcon}
                        description="Total events in scope"
                    />
                    <StatCard
                        title="Active events"
                        value={stats.active_events_count}
                        icon={ZapIcon}
                        description="Published or currently active"
                    />
                    <StatCard
                        title="Users"
                        value={stats.users_count}
                        icon={UsersIcon}
                        description="Users in scope"
                    />
                </div>

                {canViewEvents && (
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Recent events</CardTitle>
                        <Button
                            size="sm"
                            render={<Link href={route('admin.events.create')} />}
                        >
                            Create event
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentEvents.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No events yet. Create your first event to get
                                started.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        {scope.is_global && (
                                            <TableHead>Organization</TableHead>
                                        )}
                                        <TableHead>Status</TableHead>
                                        <TableHead>Starts</TableHead>
                                        <TableHead className="text-right">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentEvents.map((event) => (
                                        <TableRow key={event.id}>
                                            <TableCell className="font-medium">
                                                {event.name}
                                            </TableCell>
                                            {scope.is_global && (
                                                <TableCell>
                                                    {event.organization?.name ??
                                                        '—'}
                                                </TableCell>
                                            )}
                                            <TableCell>
                                                <EventStatusBadge
                                                    status={event.status}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                {event.starts_at ?? '—'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={route(
                                                                'admin.events.show',
                                                                event.id,
                                                            )}
                                                        />
                                                    }
                                                >
                                                    Open
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
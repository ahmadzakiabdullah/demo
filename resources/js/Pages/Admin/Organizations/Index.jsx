import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function StatusBadge({ status }) {
    return (
        <Badge variant={status === 'active' ? 'default' : 'secondary'}>
            {status}
        </Badge>
    );
}

export default function Index({ organizations, filters, types, statuses }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');
    const [status, setStatus] = useState(filters.status || '');
    const [orgToDelete, setOrgToDelete] = useState(null);

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.organizations.index'),
                { search, type, status },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, type, status]);

    const confirmDelete = () => {
        if (!orgToDelete) {
            return;
        }

        router.delete(route('admin.organizations.destroy', orgToDelete.id), {
            preserveScroll: true,
            onFinish: () => setOrgToDelete(null),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Organizations
                    </h2>
                    <Button
                        render={
                            <Link href={route('admin.organizations.create')} />
                        }
                    >
                        Add Organization
                    </Button>
                </div>
            }
        >
            <Head title="Organizations" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Manage Organizations</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-col gap-4 lg:flex-row">
                                <Input
                                    placeholder="Search by name or slug..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="lg:max-w-sm"
                                />
                                <Select
                                    value={type || 'all'}
                                    onValueChange={(value) =>
                                        setType(value === 'all' ? '' : value)
                                    }
                                >
                                    <SelectTrigger className="w-full lg:w-44">
                                        <SelectValue placeholder="All types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All types
                                        </SelectItem>
                                        {types.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {item}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={status || 'all'}
                                    onValueChange={(value) =>
                                        setStatus(value === 'all' ? '' : value)
                                    }
                                >
                                    <SelectTrigger className="w-full lg:w-40">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All statuses
                                        </SelectItem>
                                        {statuses.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {item}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Slug</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Branches</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {organizations.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
                                                className="text-center text-muted-foreground"
                                            >
                                                No organizations found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        organizations.data.map((org) => (
                                            <TableRow key={org.id}>
                                                <TableCell className="font-medium">
                                                    {org.name}
                                                </TableCell>
                                                <TableCell>{org.slug}</TableCell>
                                                <TableCell>{org.type}</TableCell>
                                                <TableCell>
                                                    <StatusBadge
                                                        status={org.status}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {org.branches_count}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            render={
                                                                <Link
                                                                    href={route(
                                                                        'admin.organizations.edit',
                                                                        org.id,
                                                                    )}
                                                                />
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                setOrgToDelete(org)
                                                            }
                                                        >
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {organizations.links.length > 3 && (
                                <div className="flex flex-wrap gap-1">
                                    {organizations.links.map((link, index) =>
                                        link.url ? (
                                            <Button
                                                key={index}
                                                variant={
                                                    link.active
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                size="sm"
                                                render={
                                                    <Link href={link.url} />
                                                }
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ) : (
                                            <Button
                                                key={index}
                                                variant="outline"
                                                size="sm"
                                                disabled
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

            <AlertDialog
                open={!!orgToDelete}
                onOpenChange={(open) => !open && setOrgToDelete(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete organization?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete{' '}
                            <strong>{orgToDelete?.name}</strong> and all its
                            branches. This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            variant="destructive"
                            onClick={confirmDelete}
                        >
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
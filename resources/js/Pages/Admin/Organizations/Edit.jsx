import InputError from '@/Components/InputError';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import OrganizationForm from '@/Pages/Admin/Organizations/OrganizationForm';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, router, useForm, usePage } from '@inertiajs/react';

export default function Edit({ organization, branches, types, statuses }) {
    const { flash } = usePage().props;

    const form = useForm({
        name: organization.name,
        slug: organization.slug,
        type: organization.type,
        timezone: organization.timezone,
        locale: organization.locale,
        status: organization.status,
    });

    const branchForm = useForm({
        name: '',
        code: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.put(route('admin.organizations.update', organization.id));
    };

    const addBranch = (e) => {
        e.preventDefault();
        branchForm.post(
            route('admin.organizations.branches.store', organization.id),
            {
                preserveScroll: true,
                onSuccess: () => branchForm.reset(),
            },
        );
    };

    const removeBranch = (branchId) => {
        router.delete(
            route('admin.organizations.branches.destroy', [
                organization.id,
                branchId,
            ]),
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Edit Organization
                </h2>
            }
        >
            <Head title={`Edit ${organization.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>{organization.name}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <OrganizationForm
                                data={form.data}
                                setData={form.setData}
                                errors={form.errors}
                                processing={form.processing}
                                types={types}
                                statuses={statuses}
                                onSubmit={submit}
                                submitLabel="Save Changes"
                                isEdit
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Branches</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <form
                                onSubmit={addBranch}
                                className="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="branch_name">Branch name</Label>
                                    <Input
                                        id="branch_name"
                                        value={branchForm.data.name}
                                        onChange={(e) =>
                                            branchForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={!!branchForm.errors.name}
                                    />
                                    <InputError message={branchForm.errors.name} />
                                </div>
                                <div className="w-full space-y-2 sm:w-40">
                                    <Label htmlFor="branch_code">Code</Label>
                                    <Input
                                        id="branch_code"
                                        value={branchForm.data.code}
                                        onChange={(e) =>
                                            branchForm.setData(
                                                'code',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={!!branchForm.errors.code}
                                    />
                                    <InputError message={branchForm.errors.code} />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={branchForm.processing}
                                >
                                    Add Branch
                                </Button>
                            </form>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Code</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {branches.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="text-center text-muted-foreground"
                                            >
                                                No branches yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        branches.map((branch) => (
                                            <TableRow key={branch.id}>
                                                <TableCell>
                                                    {branch.name}
                                                </TableCell>
                                                <TableCell>
                                                    {branch.code || '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeBranch(
                                                                branch.id,
                                                            )
                                                        }
                                                    >
                                                        Remove
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}